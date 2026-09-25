<?php
// API JSON do histórico de IMC do usuário logado.
//   GET  ?acao=listar   -> registros (do mais antigo ao mais novo)
//   POST acao=salvar    -> peso, altura
//   POST acao=excluir   -> id
// Os POSTs exigem o token CSRF (cabeçalho X-CSRF-Token).
require_once __DIR__ . '/../includes/bootstrap.php';

const LIMITE_REGISTROS = 60;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode(['ok' => $status === 200] + $dados, JSON_UNESCAPED_UNICODE);
    exit();
}

function erro(int $status, string $mensagem): never
{
    responder($status, ['mensagem' => $mensagem]);
}

/** Registros do usuário, do mais antigo ao mais novo, prontos para o gráfico. */
function listar_registros(int $usuarioId): array
{
    $stmt = db()->prepare(
        'SELECT id, peso, altura, resultado_imc, criado_em
           FROM imc WHERE usuario_id = ?
          ORDER BY criado_em DESC, id DESC LIMIT ' . LIMITE_REGISTROS
    );
    $stmt->execute([$usuarioId]);

    $registros = array_map(fn($r) => [
        'id'     => (int) $r['id'],
        'peso'   => round((float) $r['peso'], 1),
        'altura' => round((float) $r['altura'], 2),
        'imc'    => round((float) $r['resultado_imc'], 2),
        'data'   => (new DateTime($r['criado_em']))->format('Y-m-d\TH:i:s'),
    ], $stmt->fetchAll());

    return array_reverse($registros);
}

if (!usuario_logado()) {
    erro(401, 'Entre na sua conta para salvar seu IMC.');
}
$usuarioId = (int) $_SESSION['user_id'];

$acao = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['acao'] ?? '') : ($_GET['acao'] ?? '');

try {
    switch ($acao) {
        case 'listar':
            responder(200, ['registros' => listar_registros($usuarioId)]);

        case 'salvar':
            if (!csrf_valido()) {
                erro(403, 'Sessão expirada. Recarregue a página e tente de novo.');
            }

            $peso = ler_decimal($_POST['peso'] ?? '');
            $altura = ler_decimal($_POST['altura'] ?? '');
            if ($peso === null || $altura === null
                || $peso < PESO_MIN || $peso > PESO_MAX || $altura < ALTURA_MIN || $altura > ALTURA_MAX) {
                erro(422, 'Informe um peso (em kg) e uma altura (em metros) válidos.');
            }

            // O IMC é recalculado aqui: não confiamos no valor do navegador.
            $stmt = db()->prepare('INSERT INTO imc (usuario_id, peso, altura, resultado_imc) VALUES (?, ?, ?, ?)');
            $stmt->execute([$usuarioId, $peso, $altura, calcular_imc($peso, $altura)]);

            responder(200, ['mensagem' => 'IMC salvo com sucesso!', 'registros' => listar_registros($usuarioId)]);

        case 'excluir':
            if (!csrf_valido()) {
                erro(403, 'Sessão expirada. Recarregue a página e tente de novo.');
            }

            // O usuarioId no WHERE impede apagar registros de outra pessoa.
            $stmt = db()->prepare('DELETE FROM imc WHERE id = ? AND usuario_id = ?');
            $stmt->execute([(int) ($_POST['id'] ?? 0), $usuarioId]);
            if ($stmt->rowCount() === 0) {
                erro(404, 'Registro não encontrado.');
            }

            responder(200, ['mensagem' => 'Registro excluído.', 'registros' => listar_registros($usuarioId)]);

        default:
            erro(400, 'Ação inválida.');
    }
} catch (PDOException $e) {
    error_log('Erro na API de IMC: ' . $e->getMessage());
    erro(500, 'Não foi possível concluir agora. Tente novamente em instantes.');
}
