<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar(url('auth/register.php'));
}

$nome = trim($_POST['nome'] ?? '');
$genero = $_POST['genero'] ?? '';
$data_nascimento = trim($_POST['data_nascimento'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$senha = $_POST['senha'] ?? '';

$erros = validar_dados_pessoais($nome, $genero, $data_nascimento, $telefone);

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
    $erros[] = 'Informe um e-mail válido.';
}

// bcrypt ignora tudo após 72 bytes.
if (strlen($senha) < 8) {
    $erros[] = 'A senha deve ter pelo menos 8 caracteres.';
} elseif (strlen($senha) > 72) {
    $erros[] = 'A senha deve ter no máximo 72 caracteres.';
}

if (!$erros) {
    try {
        $stmt = db()->prepare('INSERT INTO usuarios (nome, genero, data_nascimento, email, telefone, senha) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$nome, $genero, $data_nascimento, $email, $telefone, password_hash($senha, PASSWORD_BCRYPT)]);

        flash_set('login_ok', 'Cadastro realizado! Entre com seu e-mail e senha.');
        flash_set('login_email', $email);
        redirecionar(url('auth/login.php'));
    } catch (PDOException $e) {
        // 1062 = chave única duplicada (e-mail já cadastrado)
        if (($e->errorInfo[1] ?? null) === 1062) {
            $erros[] = 'Já existe uma conta com este e-mail. Tente entrar.';
        } else {
            error_log('Erro no cadastro: ' . $e->getMessage());
            $erros[] = 'Não foi possível concluir o cadastro agora. Tente novamente em instantes.';
        }
    }
}

flash_set('cadastro_erros', $erros);
flash_set('cadastro_old', [
    'nome' => $nome,
    'genero' => $genero,
    'data_nascimento' => $data_nascimento,
    'email' => $email,
    'telefone' => $telefone,
]);
redirecionar(url('auth/register.php'));
