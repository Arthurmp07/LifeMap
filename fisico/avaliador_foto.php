<?php
// Entrega a foto de uma avaliação física. As fotos ficam fora do acesso público e só saem daqui para:
//   - o dono da foto;
//   - um profissional com atendimento ATIVO com o dono (ficha do paciente).
// Para todos os outros a resposta é 404, igual à de uma foto que não existe.
require_once __DIR__ . '/../includes/bootstrap.php';

if (!usuario_logado()) {
    http_response_code(401);
    exit();
}

$stmt = db()->prepare('SELECT arquivo, usuario_id FROM avaliacoes_fisicas WHERE id = ?');
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$foto = $stmt->fetch();

$meuId = (int) $_SESSION['user_id'];
$ehDono = $foto && (int) $foto['usuario_id'] === $meuId;
$autorizado = $ehDono || ($foto && ficha_liberada_para($meuId, (int) $foto['usuario_id']) !== null);

$caminho = $autorizado ? caminho_foto_avaliacao($foto['arquivo']) : '';

if ($caminho === '' || !is_file($caminho)) {
    http_response_code(404);
    exit();
}

header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($caminho));
// O dono pode reaproveitar a foto por um dia; o profissional não: se o atendimento acabar, o acesso acaba junto.
header('Cache-Control: ' . ($ehDono ? 'private, max-age=86400' : 'private, no-store'));
header('X-Content-Type-Options: nosniff');
readfile($caminho);
