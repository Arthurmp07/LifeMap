<?php
// Página "sem acesso" (403). Espera: $voltar (URL da página inicial do perfil da pessoa).
require __DIR__ . '/head.php';
require __DIR__ . '/header.php';
?>

<main id="conteudo">
    <div class="container auth">
        <section class="card auth__card">
            <h1>Sem acesso</h1>
            <p class="auth__lead">Esta página é de outro perfil. Com a conta que você está usando agora, não há nada para ver aqui.</p>
            <a class="btn btn--primary btn--lg btn--block" href="<?= e($voltar) ?>">Voltar para o início</a>
        </section>
    </div>
</main>

<?php require __DIR__ . '/footer.php'; ?>
