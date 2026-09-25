<?php
// Cabeçalho do site. Defina $paginaAtiva ('fisico', 'mental', 'ingesta', 'rotina', 'atendimento',
// 'pacientes' ou 'admin') antes de incluir para destacar o item do menu.
// O menu depende do perfil de quem está logado (usuário, profissional ou administrador).
require_once __DIR__ . '/../includes/bootstrap.php';

$paginaAtiva = $paginaAtiva ?? '';
$nomeUsuario = trim($_SESSION['user_nome'] ?? '');
$primeiroNome = $nomeUsuario !== '' ? nome_de_saudacao($nomeUsuario) : 'meu perfil';

$papel = usuario_logado() ? papel_da_sessao() : '';

// chave => [caminho, rótulo, contador de avisos (opcional)]
$itensMenu = match ($papel) {
    'profissional' => ['pacientes' => ['profissional/', 'Pacientes', 'atendimento']],
    'admin'        => ['admin' => ['admin/', 'Profissionais']],
    default        => [
        'fisico'  => ['fisico/', 'Físico'],
        'mental'  => ['mental/', 'Mental'],
        'ingesta' => ['ingesta/', 'Ingesta'],
        'rotina'  => ['rotina/', 'Rotina'],
    ] + ($papel === 'usuario' ? ['atendimento' => ['atendimento/', 'Atendimento', 'atendimento']] : []),
};

// Perfil de usuário edita o perfil; profissional e administrador só trocam a senha.
$linkConta = $papel === 'usuario' || $papel === '' ? url('perfil/') : url('auth/alterar_senha.php');
?>
<a class="skip-link" href="#conteudo">Ir para o conteúdo</a>

<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="<?= e(inicio_do_papel($papel)) ?>">
            <img src="<?= asset('img/marca/logo-simbolo.png') ?>" alt="" width="44" height="44">
            <span>Life<span class="brand__map">Map</span></span>
        </a>

        <button class="tema-toggle" type="button" id="tema-toggle" aria-pressed="false" aria-label="Modo escuro" title="Alternar entre modo claro e escuro">
            <ion-icon name="moon-outline" aria-hidden="true"></ion-icon>
        </button>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="menu-principal" aria-label="Abrir menu">
            <span class="nav-toggle__bar"></span>
            <span class="nav-toggle__bar"></span>
            <span class="nav-toggle__bar"></span>
        </button>

        <div class="site-nav" id="menu-principal">
            <nav class="site-nav__links" aria-label="Principal">
                <?php foreach ($itensMenu as $chave => $item): [$caminho, $rotulo] = $item; $aviso = $item[2] ?? ''; ?>
                    <a href="<?= url($caminho) ?>"<?= $paginaAtiva === $chave ? ' class="is-active" aria-current="page"' : '' ?>>
                        <?= $rotulo ?>
                        <?php if ($aviso): ?><span class="menu-badge" data-aviso="<?= $aviso ?>" hidden></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="site-nav__auth">
                <?php if (usuario_logado()): ?>
                    <a class="site-nav__hello<?= $paginaAtiva === 'perfil' ? ' is-active' : '' ?>" href="<?= e($linkConta) ?>"
                       title="<?= $papel === 'usuario' ? 'Meu perfil' : 'Minha conta' ?>">
                        <ion-icon name="person-circle-outline"></ion-icon>
                        <span>Olá, <?= e($primeiroNome) ?></span>
                        <?php if ($papel === 'profissional' || $papel === 'admin'): ?>
                            <span class="selo-papel"><?= e(ROTULOS_PAPEL[$papel]) ?></span>
                        <?php endif; ?>
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
