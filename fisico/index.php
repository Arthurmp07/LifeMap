<?php
$pageTitle = 'Físico';
$paginaAtiva = 'fisico';
$bodyClass = 'tema-fisico';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container">
        <div class="page-head">
            <span class="eyebrow">Físico</span>
            <h1>Cuide do seu corpo</h1>
            <p class="lead">Escolha uma ferramenta para acompanhar sua saúde e organizar seus treinos.</p>
        </div>

        <div class="hub">
            <a class="hub-card" href="<?= url('fisico/imc.php') ?>">
                <span class="hub-card__icon"><img class="pixel" src="<?= asset('img/icone-imc.png') ?>" alt=""></span>
                <h3>Cálculo de IMC</h3>
                <p>Descubra seu Índice de Massa Corporal e em que faixa você está.</p>
                <span class="hub-card__link">Calcular →</span>
            </a>

            <a class="hub-card" href="<?= url('fisico/treino.php') ?>">
                <span class="hub-card__icon"><img class="pixel" src="<?= asset('img/icone-treino.png') ?>" alt=""></span>
                <h3>Gerador de treino</h3>
                <p>Receba sugestões de exercícios de acordo com seu objetivo e faixa de idade.</p>
                <span class="hub-card__link">Gerar treino →</span>
            </a>

            <a class="hub-card" href="<?= url('fisico/verificador.php') ?>">
                <span class="hub-card__icon"><img class="pixel" src="<?= asset('img/icone-exercicios.png') ?>" alt=""></span>
                <h3>Verificador de movimento</h3>
                <p>Veja a execução correta de cada exercício em GIFs.</p>
                <span class="hub-card__link">Ver exercícios →</span>
            </a>

            <a class="hub-card" href="<?= url('fisico/avaliador.php') ?>">
                <span class="hub-card__icon"><img class="pixel" src="<?= asset('img/pilar-fisico.png') ?>" alt=""></span>
                <h3>Avaliador de físico</h3>
                <p>Tire uma foto de frente e veja postura e proporções. A foto e o resultado ficam no seu perfil.</p>
                <span class="hub-card__link">Abrir avaliador →</span>
            </a>
        </div>

        <section class="card beneficios">
            <h2>Por que o exercício físico é importante</h2>
            <ul>
                <li>Reduz o estresse e sintomas de ansiedade</li>
                <li>Melhora a qualidade do sono</li>
                <li>Melhora a aprendizagem</li>
                <li>Reduz sintomas depressivos</li>
                <li>Previne e diminui a mortalidade por doenças crônicas</li>
                <li>Melhora a força, o equilíbrio e a flexibilidade</li>
                <li>Proporciona socialização e convivência</li>
            </ul>
        </section>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
