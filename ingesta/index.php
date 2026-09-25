<?php
$pageTitle = 'Ingesta';
$paginaAtiva = 'ingesta';
$bodyClass = 'tema-ingesta';
$pageDescription = 'Entenda a pirâmide alimentar e a importância de uma alimentação saudável.';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container">
        <div class="page-head">
            <span class="eyebrow">Ingesta</span>
            <h1>Alimentação saudável</h1>
            <p class="lead">Uma alimentação equilibrada fornece os nutrientes de que o corpo precisa para funcionar bem.</p>
        </div>

        <a class="destaque" href="<?= url('ingesta/dieta.php') ?>">
            <span class="destaque__icon"><ion-icon name="restaurant-outline"></ion-icon></span>
            <span>
                <strong>Gerador de dieta</strong>
                <span>Informe seu objetivo e veja o que consumir e o que evitar.</span>
            </span>
            <ion-icon class="destaque__seta" name="arrow-forward-outline"></ion-icon>
        </a>

        <section class="card">
            <div class="piramide">
                <div class="piramide__img">
                    <img src="<?= asset('img/piramide-alimentar.png') ?>" alt="Ilustração da pirâmide alimentar, com a água na base e óleos, gorduras e doces no topo">
                </div>
                <div>
                    <h2>Pirâmide alimentar: o que é</h2>
                    <p>A pirâmide alimentar é uma representação gráfica que reúne informações importantes a respeito dos grupos de alimentos presentes em nossa dieta. Seu principal objetivo é garantir o bem-estar nutricional da população, informando sobre as porções recomendadas de cada tipo de alimento.</p>
                    <p>Os alimentos estão dispostos por nível de necessidade: a base tem maior importância e o topo, menor. Do primeiro nível (base) ao quinto (topo):</p>
                    <ol class="niveis">
                        <li><span><strong>Base:</strong> grupo da água</span></li>
                        <li><span>Grupo dos cereais, tubérculos e raízes</span></li>
                        <li><span>Grupo das hortaliças e grupo das frutas</span></li>
                        <li><span>Grupo do leite e produtos lácteos, das carnes e ovos, e das leguminosas e oleaginosas</span></li>
                        <li><span><strong>Topo:</strong> grupo dos óleos e gorduras e grupo dos açúcares e doces</span></li>
                    </ol>
                </div>
            </div>
        </section>

        <section class="card importancia">
            <h2>Importância da saúde alimentar</h2>
            <p style="margin-bottom: 0;">A nutrição faz parte da vida de todo ser humano. Com a ingestão de alimentos saudáveis, o corpo recebe os nutrientes, vitaminas e minerais necessários para manter o funcionamento adequado, inclusive prevenindo doenças como obesidade, anemia, diabetes, entre outras.</p>
        </section>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
