<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar(url('auth/login.php'));
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['password'] ?? '';

try {
    $stmt = db()->prepare('SELECT id, nome, senha, cadastro_completo, papel, ativo, trocar_senha FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Erro no login: ' . $e->getMessage());
    flash_set('login_erro', 'Não foi possível entrar agora. Tente novamente em instantes.');
    redirecionar(url('auth/login.php'));
}

if ($user && password_verify($senha, $user['senha'])) {
    // O aviso de conta desativada só aparece com a senha certa (não revela quais e-mails existem).
    if (!$user['ativo']) {
        flash_set('login_erro', 'Esta conta foi desativada. Fale com o administrador.');
        redirecionar(url('auth/login.php'));
    }

    // Novo ID de sessão ao autenticar evita fixação de sessão.
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['cadastro_completo'] = $user['cadastro_completo'];
    $_SESSION['papel'] = $user['papel'];   // só para o menu; o acesso é sempre conferido no banco

    // Senha provisória (conta criada pelo administrador): precisa ser trocada antes de tudo.
    if ($user['trocar_senha']) {
        flash_set('senha_aviso', 'Sua senha é provisória. Crie uma senha nova para continuar.');
        redirecionar(url('auth/alterar_senha.php'));
    }

    redirecionar(destino_depois_do_login(inicio_do_papel($user['papel'])));
}

flash_set('login_erro', 'E-mail ou senha incorretos.');
flash_set('login_email', $email);
redirecionar(url('auth/login.php'));
