<?php
require_once __DIR__ . '/../includes/bootstrap.php';

exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    redirecionar(url('auth/alterar_senha.php'));
}

$usuario = usuario_atual();
if ($usuario === null) {
    redirecionar(url('auth/login.php'));
}

$atual = $_POST['senha_atual'] ?? '';
$nova = $_POST['nova_senha'] ?? '';
$confirmar = $_POST['confirmar_senha'] ?? '';
$erros = [];

$stmt = db()->prepare('SELECT senha FROM usuarios WHERE id = ?');
$stmt->execute([$usuario['id']]);
$hash = (string) $stmt->fetchColumn();

if (!is_string($atual) || $atual === '' || !password_verify($atual, $hash)) {
    $erros[] = 'A senha atual está incorreta.';
}

// bcrypt ignora tudo após 72 bytes.
if (!is_string($nova) || strlen($nova) < 8) {
    $erros[] = 'A nova senha deve ter pelo menos 8 caracteres.';
} elseif (strlen($nova) > 72) {
    $erros[] = 'A nova senha deve ter no máximo 72 caracteres.';
} elseif ($nova !== $confirmar) {
    $erros[] = 'As duas senhas novas não são iguais.';
} elseif ($nova === $atual) {
    $erros[] = 'A nova senha precisa ser diferente da atual.';
}

if ($erros) {
    flash_set('senha_erros', $erros);
    redirecionar(url('auth/alterar_senha.php'));
}

$stmt = db()->prepare('UPDATE usuarios SET senha = ?, trocar_senha = 0 WHERE id = ?');
$stmt->execute([password_hash($nova, PASSWORD_BCRYPT), $usuario['id']]);

// Nova sessão depois de mudar a credencial.
session_regenerate_id(true);

if ($usuario['papel'] === 'usuario') {
    flash_set('perfil_ok', 'Senha alterada com sucesso.');
} else {
    flash_set('painel_ok', 'Senha alterada com sucesso.');
}
redirecionar(inicio_do_papel($usuario['papel']));
