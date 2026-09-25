<?php
$pageTitle = '';
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>

<main id="conteudo">
    <section class="hero">
        <div class="container hero__inner">
            <div>
                <span class="eyebrow">Bem-estar completo</span>
                <h1>Cuide do <em>corpo</em>, da <em>mente</em> e da <em>alimentação</em> em um só lugar.</h1>
                <p class="lead">Calcule seu IMC, receba sugestões de treino e dieta, aprenda a executar cada exercício e entenda mais sobre saúde mental.</p>
                <div class="hero__actions">
                    <?php if (usuario_logado()): ?>
                        <a class="btn btn--primary btn--lg" href="<?= url('fisico/') ?>">Ir para o Físico</a>
                    <?php else: ?>
                        <a class="btn btn--primary btn--lg" href="<?= url('auth/register.php') ?>">Criar minha conta</a>
                    <?php endif; ?>
                    <a class="btn btn--ghost btn--lg" href="#pilares">Conhecer os pilares</a>
                </div>
            </div>
            <div class="hero__art">
                <div class="marca-cartao">
                    <img src="<?= asset('img/marca/logo-completa.png') ?>" alt="LifeMap: seus dados, sua evolução" width="880" height="724">
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="pilares">
        <div class="container">
            <div class="section__head">
                <h2>Três pilares para o seu bem-estar</h2>
                <p>Escolha por onde começar. Cada área reúne ferramentas e informações confiáveis.</p>
            </div>

            <div class="pilares">
                <a class="pilar tema-fisico" href="<?= url('fisico/') ?>">
                    <span class="pilar__icon"><img class="pixel" src="<?= asset('img/pilar-fisico.png') ?>" alt=""></span>
                    <h3>Físico</h3>
                    <p>O bem-estar físico é a capacidade de realizar atividades físicas e desempenhar papéis sociais sem limitações físicas ou experiências de dor corporal, com bons indicadores de saúde biológicos.</p>
                    <span class="pilar__link">Explorar o Físico →</span>
                </a>

                <a class="pilar tema-mental" href="<?= url('mental/') ?>">
                    <span class="pilar__icon"><img class="pixel" src="<?= asset('img/pilar-mental.png') ?>" alt=""></span>
                    <h3>Mental</h3>
                    <p>É um estado de bem-estar no qual o indivíduo usa suas próprias habilidades para se recuperar do estresse rotineiro, ser produtivo e contribuir com sua comunidade.</p>
                    <span class="pilar__link">Explorar o Mental →</span>
                </a>

                <a class="pilar tema-ingesta" href="<?= url('ingesta/') ?>">
                    <span class="pilar__icon"><img src="<?= asset('img/piramide-alimentar.png') ?>" alt=""></span>
                    <h3>Ingesta</h3>
                    <p>Uma alimentação saudável garante todos os nutrientes necessários ao funcionamento do corpo e é essencial para o bem-estar geral.</p>
                    <span class="pilar__link">Explorar a Ingesta →</span>
                </a>
            </div>
        </div>
    </section>

    <section class="section" style="padding-top: 0;">
        <div class="container">
            <div class="section__head">
                <h2>Ferramentas</h2>
                <p>Atalhos para o que você mais vai usar.</p>
            </div>

            <div class="ferramentas">
                <a class="ferramenta tema-fisico" href="<?= url('fisico/imc.php') ?>">
                    <span class="ferramenta__icon"><ion-icon name="calculator-outline"></ion-icon></span>
                    <div><h3>Calculadora de IMC</h3><p>Descubra sua categoria e salve o resultado.</p></div>
                </a>
                <a class="ferramenta tema-fisico" href="<?= url('fisico/treino.php') ?>">
                    <span class="ferramenta__icon"><ion-icon name="barbell-outline"></ion-icon></span>
                    <div><h3>Gerador de treino</h3><p>Sugestões de exercícios para o seu objetivo.</p></div>
                </a>
                <a class="ferramenta tema-ingesta" href="<?= url('ingesta/dieta.php') ?>">
                    <span class="ferramenta__icon"><ion-icon name="restaurant-outline"></ion-icon></span>
                    <div><h3>Gerador de dieta</h3><p>O que consumir e o que evitar.</p></div>
                </a>
                <a class="ferramenta tema-fisico" href="<?= url('fisico/verificador.php') ?>">
                    <span class="ferramenta__icon"><ion-icon name="body-outline"></ion-icon></span>
                    <div><h3>Verificador de movimento</h3><p>Veja como executar cada exercício.</p></div>
                </a>
                <a class="ferramenta" href="<?= url('rotina/') ?>">
                    <span class="ferramenta__icon"><ion-icon name="calendar-outline"></ion-icon></span>
                    <div><h3>Rotina e humor</h3><p>Organize seu dia e registre como você está se sentindo.</p></div>
                </a>
                <a class="ferramenta tema-fisico" href="<?= url('fisico/avaliador.php') ?>">
                    <span class="ferramenta__icon"><ion-icon name="camera-outline"></ion-icon></span>
                    <div><h3>Avaliador de físico</h3><p>Análise de proporções pela câmera.</p></div>
                </a>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
