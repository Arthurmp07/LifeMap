<?php
// Entrega a foto de uma avaliação física, somente ao dono (as fotos ficam fora do acesso público).
require_once __DIR__ . '/../includes/bootstrap.php';

if (!usuario_logado()) {
    http_response_code(401);
    exit();
}

$stmt = db()->prepare('SELECT arquivo FROM avaliacoes_fisicas WHERE id = ? AND usuario_id = ?');
$stmt->execute([(int) ($_GET['id'] ?? 0), (int) $_SESSION['user_id']]);
$arquivo = $stmt->fetchColumn();
$caminho = $arquivo !== false ? caminho_foto_avaliacao($arquivo) : '';

if ($caminho === '' || !is_file($caminho)) {
    http_response_code(404);
    exit();
}

header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($caminho));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($caminho);
