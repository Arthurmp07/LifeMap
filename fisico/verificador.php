<?php
$pageTitle = 'Verificador de movimento';
$paginaAtiva = 'fisico';
$bodyClass = 'tema-fisico';
$pageDescription = 'Veja a execução correta de cada exercício em GIFs animados.';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';

// arquivo dentro de GIF/  =>  nome exibido
$exercicios = [
    '06301301-Mountain-Climber_Cardio_360-logo.gif'        => 'Mountain Climber Cardio',
    '46461301-abdominal-obliquo-rotacao-russa-de-quadri.gif' => 'Abdominal Oblíquo: Rotação Russa',
    'abdominal.gif'                                        => 'Abdominal',
    'agachamento-com-salto-tradicional.gif'                => 'Agachamento com Salto Tradicional',
    'agachamento-livre-1.gif'                              => 'Agachamento Livre',
    'barbell-lying-triceps-extension-skull-crusher.gif'    => 'Tríceps Testa com Barra (Skull Crusher)',
    'burpee.gif'                                           => 'Burpee',
    'costas-remada-no-smith-com-pegada-invertida.gif'      => 'Costas: Remada no Smith com Pegada Invertida',
    'desenvolvimento-para-ombros-com-halteres.gif'         => 'Desenvolvimento para Ombros com Halteres',
    'Elevacao-de-panturrilha-com-carga-em-uma-perna.gif'   => 'Elevação de Panturrilha com Carga em Uma Perna',
    'flexao-de-bracos.gif'                                 => 'Flexão de Braços',
    'levantamento-terra-deadlift-stiff-com-halteres-1.gif' => 'Levantamento Terra Stiff com Halteres',
    'ombros-elevacao-lateral-de-ombros-com-halteres.gif'   => 'Ombros: Elevação Lateral com Halteres',
    'pernas-afundo-tradicional-sem-pesos.gif'              => 'Pernas: Afundo Tradicional sem Pesos',
    'pernas-e-costas-levantamento-terra-deadlift.gif'      => 'Pernas e Costas: Levantamento Terra',
    'pernas-extensao-de-pernas-na-maquina.gif'             => 'Pernas: Extensão de Pernas na Máquina',
    'pernas-leg-press-45-tradicional.gif'                  => 'Pernas: Leg Press 45°',
    'Prancha.png'                                          => 'Prancha',
    'remada-sentado-com-cabos-e-triangulo-para-costas.gif' => 'Remada Sentado com Cabos e Triângulo para Costas',
    'rosca-biceps-martelo-com-halteres.gif'                => 'Rosca Bíceps Martelo com Halteres',
    'supino-inclinado-com-halteres.gif'                    => 'Supino Inclinado com Halteres',
    'supino-reto.gif'                                      => 'Supino Reto',
];
?>

<main id="conteudo">
    <div class="container">
        <div class="page-head">
            <p class="breadcrumb"><a href="<?= url('fisico/') ?>">← Físico</a></p>
            <span class="eyebrow">Físico</span>
            <h1>Verificador de movimento</h1>
            <p class="lead">Confira a execução correta de <?= count($exercicios) ?> exercícios antes de treinar.</p>

            <div class="busca">
                <ion-icon name="search-outline"></ion-icon>
                <label class="visually-hidden" for="busca-exercicio">Buscar exercício</label>
                <input class="input" type="search" id="busca-exercicio" placeholder="Buscar exercício (ex.: agachamento)" autocomplete="off">
            </div>
        </div>

        <div class="exercicios" id="lista-exercicios">
            <?php foreach ($exercicios as $arquivo => $nome): ?>
                <article class="exercicio" data-nome="<?= e($nome) ?>">
                    <div class="exercicio__midia">
                        <img src="<?= e(asset('gif/' . $arquivo)) ?>" alt="Demonstração do exercício <?= e($nome) ?>" loading="lazy">
                    </div>
                    <h2><?= e($nome) ?></h2>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="vazio" id="sem-resultado" hidden>Nenhum exercício encontrado para essa busca.</p>
    </div>
</main>

<script src="<?= asset('js/verificador.js') ?>"></script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
