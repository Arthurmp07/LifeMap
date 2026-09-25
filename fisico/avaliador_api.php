<?php
// API JSON do avaliador físico (usuário logado).
//   GET  ?acao=listar        avaliações salvas, da mais recente à mais antiga
//   POST acao=interpretar    razao, ombros, quadril, cabeca, tronco, corpo_inteiro -> interpretação (sem salvar)
//   POST acao=salvar         as mesmas medidas + foto (JPEG, campo "foto") -> salva
//   POST acao=excluir        id
// Os POSTs exigem o token CSRF (cabeçalho X-CSRF-Token).
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode(['ok' => $status === 200] + $dados, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    exit();
}

function erro(int $status, string $mensagem): never
{
    responder($status, ['mensagem' => $mensagem]);
}

/** Lista completa para a tela: cada avaliação já comparada com a anterior. */
function avaliacoes_em_json(int $usuarioId): array
{
    $todas = listar_avaliacoes($usuarioId);
    $saida = [];
    foreach ($todas as $i => $avaliacao) {
        $saida[] = avaliacao_para_json($avaliacao, $todas[$i + 1] ?? null);
    }
    return $saida;
}

if (!usuario_logado()) {
    erro(401, 'Entre na sua conta para usar o avaliador.');
}
$usuarioId = (int) $_SESSION['user_id'];

$ehPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$acao = $ehPost ? (string) ($_POST['acao'] ?? '') : (string) ($_GET['acao'] ?? '');

if ($ehPost && !csrf_valido()) {
    erro(403, 'Sessão expirada. Recarregue a página e tente de novo.');
}
if (!$ehPost && $acao !== 'listar') {
    erro(405, 'Método não permitido.');
}

try {
    switch ($acao) {
        case 'listar':
            responder(200, ['avaliacoes' => avaliacoes_em_json($usuarioId)]);

        case 'interpretar':
            $medidas = ler_medidas_avaliacao($_POST);
            if (!$medidas) {
                erro(422, 'Não consegui ler as medidas desta foto. Tire outra.');
            }
            responder(200, ['interpretacao' => interpretar_avaliacao($medidas)]);

        case 'salvar':
            $medidas = ler_medidas_avaliacao($_POST);
            if (!$medidas) {
                erro(422, 'Não consegui ler as medidas desta foto. Tire outra.');
            }

            $stmt = db()->prepare('SELECT COUNT(*) FROM avaliacoes_fisicas WHERE usuario_id = ?');
            $stmt->execute([$usuarioId]);
            if ((int) $stmt->fetchColumn() >= AVALIACAO_MAX_POR_USUARIO) {
                erro(422, 'Você chegou ao limite de ' . AVALIACAO_MAX_POR_USUARIO . ' avaliações. Exclua alguma antiga para salvar outra.');
            }

            [$arquivo, $problema] = guardar_foto_enviada($_FILES['foto'] ?? null);
            if ($arquivo === null) {
                erro(422, $problema);
            }

            try {
                $stmt = db()->prepare(
                    'INSERT INTO avaliacoes_fisicas
                        (usuario_id, arquivo, razao_ombros_quadril, inclinacao_ombros, inclinacao_quadril,
                         desvio_cabeca, inclinacao_tronco, corpo_inteiro)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$usuarioId, $arquivo, $medidas['razao'], $medidas['ombros'], $medidas['quadril'],
                    $medidas['cabeca'], $medidas['tronco'], $medidas['corpo_inteiro'] ? 1 : 0]);
            } catch (PDOException $e) {
                @unlink(caminho_foto_avaliacao($arquivo));   // não deixa foto órfã
                throw $e;
            }

            responder(200, ['mensagem' => 'Avaliação salva no seu perfil.', 'avaliacoes' => avaliacoes_em_json($usuarioId)]);

        case 'excluir':
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = db()->prepare('SELECT arquivo FROM avaliacoes_fisicas WHERE id = ? AND usuario_id = ?');
            $stmt->execute([$id, $usuarioId]);
            $arquivo = $stmt->fetchColumn();
            if ($arquivo === false) {
                erro(404, 'Avaliação não encontrada.');
            }

            db()->prepare('DELETE FROM avaliacoes_fisicas WHERE id = ? AND usuario_id = ?')->execute([$id, $usuarioId]);
            @unlink(caminho_foto_avaliacao($arquivo));
            responder(200, ['mensagem' => 'Avaliação excluída.', 'avaliacoes' => avaliacoes_em_json($usuarioId)]);

        default:
            erro(400, 'Ação inválida.');
    }
} catch (PDOException $e) {
    error_log('Erro no avaliador físico: ' . $e->getMessage());
    erro(500, 'Não foi possível concluir agora. Tente novamente em instantes.');
}
