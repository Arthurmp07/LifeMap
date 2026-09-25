<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar(url('auth/login.php'));
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['password'] ?? '';

try {
    $stmt = db()->prepare('SELECT id, nome, senha, cadastro_completo FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Erro no login: ' . $e->getMessage());
    flash_set('login_erro', 'Não foi possível entrar agora. Tente novamente em instantes.');
    redirecionar(url('auth/login.php'));
}

if ($user && password_verify($senha, $user['senha'])) {
    // Novo ID de sessão ao autenticar evita fixação de sessão.
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['cadastro_completo'] = $user['cadastro_completo'];
    redirecionar(destino_depois_do_login());
}

flash_set('login_erro', 'E-mail ou senha incorretos.');
flash_set('login_email', $email);
redirecionar(url('auth/login.php'));
