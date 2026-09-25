<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (usuario_logado()) {
    redirecionar(inicio_do_papel(papel_da_sessao()));
}

$erros = flash_get('cadastro_erros') ?? [];
$antigo = flash_get('cadastro_old') ?? [];

$pageTitle = 'Cadastre-se';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container auth">
        <section class="card auth__card auth__card--wide">
            <h1>Crie sua conta</h1>
            <p class="auth__lead">É rápido e gratuito. Com a conta você salva seu IMC e acompanha sua evolução.</p>

            <?php if ($erros): ?>
                <div class="alert alert--erro" role="alert">
                    <ul>
                        <?php foreach ($erros as $mensagem): ?>
                            <li><?= e($mensagem) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?= url('auth/processar_cadastro.php') ?>" method="POST">
                <div class="field">
                    <label for="nome">Nome completo</label>
                    <input class="input" type="text" id="nome" name="nome" value="<?= e($antigo['nome'] ?? '') ?>"
                           maxlength="100" autocomplete="name" required>
                </div>

                <fieldset class="fieldset">
                    <legend>Gênero</legend>
                    <div class="chip-group">
                        <?php foreach (ROTULOS_GENERO as $valor => $rotulo): ?>
                            <label class="chip">
                                <input type="radio" name="genero" value="<?= $valor ?>"<?= ($antigo['genero'] ?? '') === $valor ? ' checked' : '' ?> required>
                                <span><?= $rotulo ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="form-row">
                    <div class="field">
                        <label for="data_nascimento">Data de nascimento</label>
                        <input class="input" type="date" id="data_nascimento" name="data_nascimento"
                               value="<?= e($antigo['data_nascimento'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"
                               autocomplete="bday" required>
                    </div>
                    <div class="field">
                        <label for="telefone">Telefone</label>
                        <input class="input" type="tel" id="telefone" name="telefone" value="<?= e($antigo['telefone'] ?? '') ?>"
                               placeholder="(11) 91234-5678" inputmode="numeric" maxlength="15" autocomplete="tel"
                               data-mascara="telefone" required>
                    </div>
                </div>

                <div class="field">
                    <label for="email">E-mail</label>
                    <input class="input" type="email" id="email" name="email" value="<?= e($antigo['email'] ?? '') ?>"
                           placeholder="voce@exemplo.com" maxlength="100" autocomplete="email" required>
                </div>

                <div class="field">
                    <label for="senha">Senha</label>
                    <input class="input" type="password" id="senha" name="senha" minlength="8" maxlength="72"
                           autocomplete="new-password" required>
                    <p class="field__hint">Mínimo de 8 caracteres.</p>
                </div>

                <button type="submit" class="btn btn--primary btn--lg btn--block">Criar conta</button>
            </form>

            <p class="auth__alt">Já tem uma conta? <a href="<?= url('auth/login.php') ?>">Entre</a></p>
        </section>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
