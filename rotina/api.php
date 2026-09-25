<?php
// API JSON da rotina (eventos) e do humor do usuário logado.
//   GET  ?acao=mes&ano=2026&mes=9     eventos e humores do mês (grade de 6 semanas)
//   POST acao=salvar                  cria (com repetição opcional) ou edita um evento
//   POST acao=excluir                 id, escopo=este|serie
//   POST acao=humor                   data, nivel (1-5), nota (opcional)
//   POST acao=humor_excluir           data
// Os POSTs exigem o token CSRF (cabeçalho X-CSRF-Token).
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode(['ok' => $status === 200] + $dados, JSON_UNESCAPED_UNICODE);
    exit();
}

function erro(int $status, string $mensagem, array $extra = []): never
{
    responder($status, ['mensagem' => $mensagem] + $extra);
}

function texto_post(string $campo): string
{
    return trim((string) ($_POST[$campo] ?? ''));
}

/** Eventos que tocam o período [$de, $ate) e humores dos dias do período. */
function dados_do_periodo(int $usuarioId, DateTimeImmutable $de, DateTimeImmutable $ate): array
{
    $stmt = db()->prepare(
        'SELECT id, titulo, categoria, inicio, fim, observacao, serie_id FROM eventos
          WHERE usuario_id = ? AND inicio < ? AND fim > ? ORDER BY inicio, id'
    );
    $stmt->execute([$usuarioId, $ate->format('Y-m-d H:i:s'), $de->format('Y-m-d H:i:s')]);
    $eventos = array_map('evento_para_json', $stmt->fetchAll());

    $stmt = db()->prepare('SELECT data, nivel, nota FROM humor WHERE usuario_id = ? AND data >= ? AND data < ?');
    $stmt->execute([$usuarioId, $de->format('Y-m-d'), $ate->format('Y-m-d')]);
    $humor = [];
    foreach ($stmt->fetchAll() as $linha) {
        $humor[$linha['data']] = ['nivel' => (int) $linha['nivel'], 'nota' => $linha['nota'] ?? ''];
    }

    return ['eventos' => $eventos, 'humor' => (object) $humor];
}

if (!usuario_logado()) {
    erro(401, 'Entre na sua conta para usar a rotina.');
}
$usuarioId = (int) $_SESSION['user_id'];

$ehPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$acao = $ehPost ? texto_post('acao') : (string) ($_GET['acao'] ?? '');

if ($ehPost && !csrf_valido()) {
    erro(403, 'Sessão expirada. Recarregue a página e tente de novo.');
}
if (!$ehPost && $acao !== 'mes') {
    erro(405, 'Método não permitido.');
}

try {
    switch ($acao) {
        case 'mes':
            $ano = (int) ($_GET['ano'] ?? 0);
            $mes = (int) ($_GET['mes'] ?? 0);
            if ($ano < 2000 || $ano > 2100 || $mes < 1 || $mes > 12) {
                erro(400, 'Mês inválido.');
            }

            // A grade começa no domingo da semana do dia 1 e mostra 6 semanas.
            $primeiro = new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes));
            $de = $primeiro->modify('-' . (int) $primeiro->format('w') . ' days');
            $ate = $de->modify('+42 days');

            responder(200, ['de' => $de->format('Y-m-d')] + dados_do_periodo($usuarioId, $de, $ate));

        case 'salvar':
            $id = (int) ($_POST['id'] ?? 0);
            $titulo = texto_post('titulo');
            $categoria = texto_post('categoria');
            $data = ler_data(texto_post('data'));
            $horaInicio = texto_post('hora_inicio');
            $horaFim = texto_post('hora_fim');
            $observacao = texto_post('observacao');
            $repetir = texto_post('repetir') ?: 'nenhuma';
            $ate = ler_data(texto_post('ate'));
            $forcar = texto_post('forcar') === '1';

            if ($titulo === '' || mb_strlen($titulo) > EVENTO_TITULO_MAX) {
                erro(422, 'Dê um título ao evento (até ' . EVENTO_TITULO_MAX . ' caracteres).');
            }
            if (!categoria_evento_valida($categoria)) {
                erro(422, 'Escolha uma categoria.');
            }
            if (!$data || $data->format('Y') < 2000 || $data->format('Y') > 2100) {
                erro(422, 'Informe uma data válida.');
            }
            if (!hora_valida($horaInicio) || !hora_valida($horaFim)) {
                erro(422, 'Informe o horário de início e de fim.');
            }
            $intervalo = intervalo_do_evento($data, $horaInicio, $horaFim);
            if ($intervalo === null) {
                erro(422, 'O fim precisa ser diferente do início.');
            }
            if (mb_strlen($observacao) > EVENTO_OBSERVACAO_MAX) {
                erro(422, 'A observação pode ter até ' . EVENTO_OBSERVACAO_MAX . ' caracteres.');
            }

            // Lista de ocorrências: uma só, ou várias quando há repetição (só ao criar).
            if ($id === 0 && $repetir !== 'nenhuma') {
                if (!in_array($repetir, REPETICOES_EVENTO, true)) {
                    erro(422, 'Repetição inválida.');
                }
                if (!$ate || $ate < $data || $ate > $data->modify('+1 year')) {
                    erro(422, 'Escolha até quando repetir (no máximo 1 ano).');
                }
                $datas = datas_da_repeticao($data, $repetir, $ate);
                if (!$datas) {
                    erro(422, 'Nenhuma data cai nesse período de repetição.');
                }
                if (count($datas) > REPETICAO_MAX_OCORRENCIAS) {
                    erro(422, 'A repetição tem mais de ' . REPETICAO_MAX_OCORRENCIAS . ' ocorrências. Escolha um período menor.');
                }
            } else {
                $datas = [$data];
            }

            $ocorrencias = [];
            $conflitos = [];
            foreach ($datas as $dia) {
                [$inicio, $fim] = intervalo_do_evento($dia, $horaInicio, $horaFim);
                $ocorrencias[] = [$inicio, $fim];
                foreach (eventos_em_conflito($usuarioId, $inicio, $fim, $id) as $c) {
                    $conflitos[] = $c;
                }
            }

            // Reserva de horário: avisa da sobreposição e só grava se a pessoa confirmar.
            if ($conflitos && !$forcar) {
                $quantos = count($conflitos);
                erro(409, $quantos === 1
                    ? 'Este horário já está reservado.'
                    : 'Este horário se sobrepõe a ' . $quantos . ' eventos que você já tem.', [
                    'conflito'  => true,
                    'conflitos' => array_map(
                        fn($c) => evento_para_json($c + ['observacao' => null, 'serie_id' => null]),
                        array_slice($conflitos, 0, 5)
                    ),
                ]);
            }

            $pdo = db();
            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE eventos SET titulo = ?, categoria = ?, inicio = ?, fim = ?, observacao = ?
                      WHERE id = ? AND usuario_id = ?'
                );
                [$inicio, $fim] = $ocorrencias[0];
                $stmt->execute([$titulo, $categoria, $inicio->format('Y-m-d H:i:s'), $fim->format('Y-m-d H:i:s'),
                    $observacao !== '' ? $observacao : null, $id, $usuarioId]);

                // rowCount() é 0 quando nada mudou; então confirma que o evento existe.
                $existe = $pdo->prepare('SELECT COUNT(*) FROM eventos WHERE id = ? AND usuario_id = ?');
                $existe->execute([$id, $usuarioId]);
                if (!$existe->fetchColumn()) {
                    erro(404, 'Evento não encontrado.');
                }
                responder(200, ['mensagem' => 'Evento atualizado.']);
            }

            $serie = count($ocorrencias) > 1 ? bin2hex(random_bytes(8)) : null;
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO eventos (usuario_id, titulo, categoria, inicio, fim, observacao, serie_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($ocorrencias as [$inicio, $fim]) {
                $stmt->execute([$usuarioId, $titulo, $categoria, $inicio->format('Y-m-d H:i:s'), $fim->format('Y-m-d H:i:s'),
                    $observacao !== '' ? $observacao : null, $serie]);
            }
            $pdo->commit();

            $n = count($ocorrencias);
            responder(200, ['mensagem' => $n > 1 ? "$n eventos criados." : 'Evento criado.', 'criados' => $n]);

        case 'excluir':
            $id = (int) ($_POST['id'] ?? 0);
            $escopo = texto_post('escopo') === 'serie' ? 'serie' : 'este';

            $stmt = db()->prepare('SELECT serie_id FROM eventos WHERE id = ? AND usuario_id = ?');
            $stmt->execute([$id, $usuarioId]);
            $linha = $stmt->fetch();
            if (!$linha) {
                erro(404, 'Evento não encontrado.');
            }

            if ($escopo === 'serie' && $linha['serie_id'] !== null) {
                $stmt = db()->prepare('DELETE FROM eventos WHERE serie_id = ? AND usuario_id = ?');
                $stmt->execute([$linha['serie_id'], $usuarioId]);
                $n = $stmt->rowCount();
                responder(200, ['mensagem' => $n . ' eventos excluídos.', 'excluidos' => $n]);
            }

            $stmt = db()->prepare('DELETE FROM eventos WHERE id = ? AND usuario_id = ?');
            $stmt->execute([$id, $usuarioId]);
            responder(200, ['mensagem' => 'Evento excluído.', 'excluidos' => 1]);

        case 'humor':
            $data = ler_data(texto_post('data'));
            $nivel = (int) ($_POST['nivel'] ?? 0);

            if (!$data) {
                erro(422, 'Data inválida.');
            }
            if ($data > new DateTimeImmutable('today')) {
                erro(422, 'Você só pode registrar o humor de hoje ou de dias anteriores.');
            }
            if (!nivel_humor_valido($nivel)) {
                erro(422, 'Escolha como você está se sentindo.');
            }

            // A anotação é opcional: se não veio no pedido, mantém a que já estava salva.
            $temNota = array_key_exists('nota', $_POST);
            $nota = texto_post('nota');
            if (mb_strlen($nota) > HUMOR_NOTA_MAX) {
                erro(422, 'A anotação pode ter até ' . HUMOR_NOTA_MAX . ' caracteres.');
            }

            $sql = 'INSERT INTO humor (usuario_id, data, nivel, nota) VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE nivel = VALUES(nivel)' . ($temNota ? ', nota = VALUES(nota)' : '');
            db()->prepare($sql)->execute([$usuarioId, $data->format('Y-m-d'), $nivel, $nota !== '' ? $nota : null]);

            $stmt = db()->prepare('SELECT nivel, nota FROM humor WHERE usuario_id = ? AND data = ?');
            $stmt->execute([$usuarioId, $data->format('Y-m-d')]);
            $linha = $stmt->fetch();
            responder(200, [
                'mensagem' => 'Humor registrado.',
                'humor'    => ['nivel' => (int) $linha['nivel'], 'nota' => $linha['nota'] ?? ''],
            ]);

        case 'humor_excluir':
            $data = ler_data(texto_post('data'));
            if (!$data) {
                erro(422, 'Data inválida.');
            }
            $stmt = db()->prepare('DELETE FROM humor WHERE usuario_id = ? AND data = ?');
            $stmt->execute([$usuarioId, $data->format('Y-m-d')]);
            responder(200, ['mensagem' => 'Registro de humor removido.']);

        default:
            erro(400, 'Ação inválida.');
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erro na API da rotina: ' . $e->getMessage());
    erro(500, 'Não foi possível concluir agora. Tente novamente em instantes.');
}
