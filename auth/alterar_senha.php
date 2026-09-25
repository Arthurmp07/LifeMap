<?php
// Troca de senha. Serve a todos os perfis; para contas com senha provisória é a única página liberada.
require_once __DIR__ . '/../includes/bootstrap.php';

exigir_login();
$usuario = usuario_atual();
if ($usuario === null) {
    redirecionar(url('auth/login.php'));
}

$provisoria = (bool) $usuario['trocar_senha'];
$aviso = flash_get('senha_aviso');
$erros = flash_get('senha_erros') ?? [];

$pageTitle = 'Alterar senha';
$paginaAtiva = $usuario['papel'] === 'usuario' ? 'perfil' : '';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container auth">
        <section class="card auth__card">
            <h1>Alterar senha</h1>
            <p class="auth__lead">
                Conta de <strong><?= e($usuario['nome']) ?></strong> (<?= e($usuario['email']) ?>) · <?= e(ROTULOS_PAPEL[$usuario['papel']]) ?>
            </p>

            <?php if ($aviso): ?>
                <div class="alert alert--aviso" role="status"><?= e($aviso) ?></div>
            <?php endif; ?>
            <?php if ($erros): ?>
                <div class="alert alert--erro" role="alert">
                    <ul>
                        <?php foreach ($erros as $mensagem): ?>
                            <li><?= e($mensagem) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?= url('auth/processar_alterar_senha.php') ?>" method="POST">
                <?= csrf_campo() ?>

                <div class="field">
                    <label for="senha_atual"><?= $provisoria ? 'Senha provisória' : 'Senha atual' ?></label>
                    <input class="input" type="password" id="senha_atual" name="senha_atual" autocomplete="current-password" required>
                </div>

                <div class="field">
                    <label for="nova_senha">Nova senha</label>
                    <input class="input" type="password" id="nova_senha" name="nova_senha" minlength="8" maxlength="72"
                           autocomplete="new-password" aria-describedby="dica-senha" required>
                    <p class="field__hint" id="dica-senha">De 8 a 72 caracteres.</p>
                </div>

                <div class="field">
                    <label for="confirmar_senha">Repita a nova senha</label>
                    <input class="input" type="password" id="confirmar_senha" name="confirmar_senha" minlength="8" maxlength="72"
                           autocomplete="new-password" required>
                </div>

                <button type="submit" class="btn btn--primary btn--lg btn--block">Salvar nova senha</button>
            </form>

            <?php if (!$provisoria): ?>
                <p class="auth__alt"><a href="<?= e(inicio_do_papel($usuario['papel'])) ?>">Voltar</a></p>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
