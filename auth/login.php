<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (usuario_logado()) {
    redirecionar(url(''));
}

$erro = flash_get('login_erro');
$sucesso = flash_get('login_ok');
$emailAnterior = flash_get('login_email') ?? '';

$pageTitle = 'Entrar';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container auth">
        <section class="card auth__card">
            <h1>Bem-vindo de volta</h1>
            <p class="auth__lead">Entre para salvar seu IMC e acompanhar seu progresso.</p>

            <?php if ($sucesso): ?>
                <div class="alert alert--ok" role="status"><?= e($sucesso) ?></div>
            <?php endif; ?>
            <?php if ($erro): ?>
                <div class="alert alert--erro" role="alert"><?= e($erro) ?></div>
            <?php endif; ?>

            <form action="<?= url('auth/processar_login.php') ?>" method="POST">
                <div class="field">
                    <label for="email">E-mail</label>
                    <input class="input" type="email" id="email" name="email" value="<?= e($emailAnterior) ?>"
                           placeholder="voce@exemplo.com" autocomplete="email" required>
                </div>

                <div class="field">
                    <label for="password">Senha</label>
                    <input class="input" type="password" id="password" name="password"
                           placeholder="Digite sua senha" autocomplete="current-password" required>
                </div>

                <button type="submit" class="btn btn--primary btn--lg btn--block">Entrar</button>
            </form>

            <p class="auth__alt">Ainda não tem conta? <a href="<?= url('auth/register.php') ?>">Cadastre-se</a></p>
        </section>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
