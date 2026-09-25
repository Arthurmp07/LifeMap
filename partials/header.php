<?php
// Cabeçalho do site. Defina $paginaAtiva ('fisico', 'mental', 'ingesta' ou 'rotina')
// antes de incluir para destacar o item do menu.
require_once __DIR__ . '/../includes/bootstrap.php';

$paginaAtiva = $paginaAtiva ?? '';
$nomeUsuario = trim($_SESSION['user_nome'] ?? '');
$primeiroNome = $nomeUsuario !== '' ? explode(' ', $nomeUsuario)[0] : 'meu perfil';

$itensMenu = [
    'fisico'  => ['fisico/', 'Físico'],
    'mental'  => ['mental/', 'Mental'],
    'ingesta' => ['ingesta/', 'Ingesta'],
    'rotina'  => ['rotina/', 'Rotina'],
];
?>
<a class="skip-link" href="#conteudo">Ir para o conteúdo</a>

<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="<?= url('') ?>">
            <img src="<?= asset('img/marca/logo-simbolo.png') ?>" alt="" width="44" height="44">
            <span>Life<span class="brand__map">Map</span></span>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="menu-principal" aria-label="Abrir menu">
            <span class="nav-toggle__bar"></span>
            <span class="nav-toggle__bar"></span>
            <span class="nav-toggle__bar"></span>
        </button>

        <div class="site-nav" id="menu-principal">
            <nav class="site-nav__links" aria-label="Principal">
                <?php foreach ($itensMenu as $chave => [$caminho, $rotulo]): ?>
                    <a href="<?= url($caminho) ?>"<?= $paginaAtiva === $chave ? ' class="is-active" aria-current="page"' : '' ?>><?= $rotulo ?></a>
                <?php endforeach; ?>
            </nav>

            <div class="site-nav__auth">
                <?php if (usuario_logado()): ?>
                    <a class="site-nav__hello<?= $paginaAtiva === 'perfil' ? ' is-active' : '' ?>" href="<?= url('perfil/') ?>">
                        <ion-icon name="person-circle-outline"></ion-icon>
                        <span>Olá, <?= e($primeiroNome) ?></span>
                    </a>
                    <form action="<?= url('auth/logout.php') ?>" method="POST">
                        <?= csrf_campo() ?>
                        <button type="submit" class="btn btn--ghost btn--sm">Sair</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn--ghost btn--sm" href="<?= url('auth/login.php') ?>">Entrar</a>
                    <a class="btn btn--primary btn--sm" href="<?= url('auth/register.php') ?>">Cadastre-se</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
