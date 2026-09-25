<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="static/css/login.css">
</head>
<body>
<div class="login-container">
    <h2>Login</h2>
    <form action="processa_login.php" method="POST">
        <label for="email">E-mail:</label>
        <input type="email" id="email" name="email" placeholder="Digite seu e-mail" required>

        <label for="password">Senha:</label>
        <div class="password-field">
            <img src="icone_cadeado.png" alt="Ícone de cadeado">
            <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
        </div>
        <button type="submit">Entrar</button>
    </form>
</div>
</body>
</html>
