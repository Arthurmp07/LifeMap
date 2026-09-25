<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Uma simulação enviada pelo formulário tem prioridade e é exibida uma única vez;
// sem ela, quem está logado vê o treino do próprio perfil.
$simulado = $_SESSION['treino'] ?? null;
$erro = $_SESSION['erro'] ?? null;
unset($_SESSION['treino'], $_SESSION['erro']);

$doPerfil = ($simulado || $erro) ? null : plano_do_perfil('treino');

$tipo = 'treino';
$paginaUrl = url('fisico/treino.php');
$formAction = url('fisico/processar_treino.php');
$plano = $simulado ?? ($doPerfil['plano'] ?? null);
$origem = $simulado ? 'simulacao' : 'perfil';
$faltando = $doPerfil['faltando'] ?? [];
$logado = usuario_logado();
$prefill = perfil_para_formulario();

$pageTitle = 'Gerador de treino';
$paginaAtiva = 'fisico';
$bodyClass = 'tema-fisico';
$pageDescription = 'Receba sugestões de treino de acordo com seu perfil, objetivo e faixa de idade.';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container">
        <div class="page-head">
            <p class="breadcrumb"><a href="<?= url('fisico/') ?>">← Físico</a></p>
            <span class="eyebrow">Físico</span>
            <h1>Gerador de treino</h1>
            <p class="lead"><?= $logado
                ? 'Seu treino segue o objetivo e a idade do seu perfil. Se quiser, simule outra combinação.'
                : 'Conte um pouco sobre você e receba uma sugestão de treino para o seu objetivo.' ?></p>
        </div>

        <?php require __DIR__ . '/../partials/gerador.php'; ?>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
