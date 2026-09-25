<?php
// Cria (ou promove) uma conta de Administrador. Só roda na linha de comando:
//
//   php tools/criar_admin.php "Nome Completo" email@exemplo.com
//   php tools/criar_admin.php "Nome Completo" email@exemplo.com --promover
//
// - Conta nova: gera uma senha provisória (mostrada só agora); a pessoa troca no primeiro acesso.
// - Conta que já existe como usuário: só vira administradora com --promover (mantém a senha dela;
//   depois disso ela deixa de usar as telas de usuário).
// - Conta que já é administradora: recebe uma nova senha provisória (recuperação de acesso).
//
// No XAMPP: C:\xampp\php\php.exe tools\criar_admin.php "Nome" email

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit();
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/includes/db.php';
require BASE_PATH . '/includes/senha.php';

$args = array_slice($argv, 1);
$promover = in_array('--promover', $args, true);
$args = array_values(array_filter($args, fn($a) => $a !== '--promover'));

if (count($args) !== 2) {
    fwrite(STDERR, "Uso: php tools/criar_admin.php \"Nome Completo\" email@exemplo.com [--promover]\n");
    exit(1);
}
[$nome, $email] = [trim($args[0]), trim($args[1])];

if (mb_strlen($nome) < 3 || mb_strlen($nome) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
    fwrite(STDERR, "Informe um nome (3 a 100 letras) e um e-mail válido.\n");
    exit(1);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id, papel FROM usuarios WHERE email = ?');
$stmt->execute([$email]);
$existente = $stmt->fetch();

if (!$existente) {
    $senha = senha_provisoria();
    $pdo->prepare("INSERT INTO usuarios (nome, email, telefone, senha, papel, trocar_senha) VALUES (?, ?, '', ?, 'admin', 1)")
        ->execute([$nome, $email, password_hash($senha, PASSWORD_BCRYPT)]);
    echo "Administrador criado: $nome <$email>\nSenha provisória: $senha\n(troque no primeiro acesso)\n";
} elseif ($existente['papel'] === 'admin') {
    $senha = senha_provisoria();
    $pdo->prepare('UPDATE usuarios SET senha = ?, trocar_senha = 1, ativo = 1 WHERE id = ?')
        ->execute([password_hash($senha, PASSWORD_BCRYPT), $existente['id']]);
    echo "Esta conta já era administradora. Nova senha provisória: $senha\n(troque no primeiro acesso)\n";
} elseif ($existente['papel'] === 'usuario' && $promover) {
    $pdo->prepare("UPDATE usuarios SET papel = 'admin', ativo = 1 WHERE id = ?")->execute([$existente['id']]);
    echo "A conta $email agora é administradora e mantém a senha atual.\n";
} else {
    fwrite(STDERR, "Já existe uma conta {$existente['papel']} com este e-mail. "
        . ($existente['papel'] === 'usuario' ? "Use --promover para torná-la administradora.\n" : "Ela não pode virar administradora por aqui.\n"));
    exit(1);
}
