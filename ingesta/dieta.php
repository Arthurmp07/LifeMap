<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Uma simulação enviada pelo formulário tem prioridade e é exibida uma única vez;
// sem ela, quem está logado vê a dieta do próprio perfil.
$simulado = $_SESSION['dieta'] ?? null;
$erro = $_SESSION['erro'] ?? null;
unset($_SESSION['dieta'], $_SESSION['erro']);

$doPerfil = ($simulado || $erro) ? null : plano_do_perfil('dieta');

$tipo = 'dieta';
$paginaUrl = url('ingesta/dieta.php');
$formAction = url('ingesta/processar_dieta.php');
$plano = $simulado ?? ($doPerfil['plano'] ?? null);
$origem = $simulado ? 'simulacao' : 'perfil';
$faltando = $doPerfil['faltando'] ?? [];
$logado = usuario_logado();
$prefill = perfil_para_formulario();

$pageTitle = 'Gerador de dieta';
$paginaAtiva = 'ingesta';
$bodyClass = 'tema-ingesta';
$pageDescription = 'Descubra o que consumir e o que evitar de acordo com o seu perfil e objetivo.';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container">
        <div class="page-head">
            <p class="breadcrumb"><a href="<?= url('ingesta/') ?>">← Ingesta</a></p>
            <span class="eyebrow">Ingesta</span>
            <h1>Gerador de dieta</h1>
            <p class="lead"><?= $logado
                ? 'Sua dieta segue o objetivo e a idade do seu perfil. Se quiser, simule outra combinação.'
                : 'Conte um pouco sobre você e veja quais alimentos priorizar e quais evitar.' ?></p>
        </div>

        <?php require __DIR__ . '/../partials/gerador.php'; ?>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
