<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
    <link rel="stylesheet" href="static/css/register.css">
</head>
<body>
<section class="register-container">
    <h2>Cadastre-se</h2>
    <form action="processa_cadastro.php" method="POST">
        <label for="nome">Nome Completo:</label>
        <input type="text" id="nome" name="nome" required>

        <label>Gênero:</label>
        <div class="gender-options">
            <label class="radio-option">
                <input type="radio" name="genero" value="feminino" required>
                <span>Feminino</span>
            </label>
            <label class="radio-option">
                <input type="radio" name="genero" value="masculino">
                <span>Masculino</span>
            </label>
        </div>

        <label for="data_nascimento">Data de Nascimento:</label>
        <input type="text" id="data_nascimento" name="data_nascimento" placeholder="__/__/____" required>

        <label for="email">E-mail:</label>
        <input type="email" id="email" name="email" required>

        <label for="telefone">Telefone:</label>
        <input type="text" id="telefone" name="telefone" placeholder="(__) _____-____" required>

        <label for="senha">Senha:</label>
        <div class="password-field">
            <ion-icon name="lock-closed-outline"></ion-icon>
            <input type="password" id="senha" name="senha" required>
        </div>

        <button type="submit">Cadastrar</button>
        <p class="login-link">Já tem uma conta? <a href="login.php">Entre</a>.</p>
    </form>
</section>
</body>
</html>
