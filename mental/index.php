<?php
$pageTitle = 'Saúde mental';
$paginaAtiva = 'mental';
$bodyClass = 'tema-mental';
$pageDescription = 'Entenda os principais transtornos mentais, seus sinais e a importância de cuidar da saúde mental.';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';

$uol2020 = ['UOL VivaBem', 'https://www.uol.com.br/vivabem/noticias/redacao/2020/09/15/os-10-transtornos-mentais-mais-comuns-saiba-identificar-os-seus-sinais.html'];

$condicoes = [
    [
        'titulo' => 'Ansiedade',
        'texto' => [
            'O Transtorno de Ansiedade Generalizada (TAG) é diferente dos sentimentos normais de ansiedade. Sua principal característica é uma preocupação intensa e persistente sobre situações normais e cotidianas. Quem tem este tipo de transtorno pode se preocupar incontrolavelmente com algo, várias vezes ao dia, mesmo que não haja motivo real para isso.',
        ],
        'lista_titulo' => 'Sintomas',
        'lista' => [
            'Dificuldade de concentração',
            'Dificuldade de dormir',
            'Tensão muscular',
            'Dores de estômago',
            'Suor nas mãos, tremores, batimento cardíaco acelerado',
            'Dormência ou formigamento em diferentes partes do corpo',
            'Irritabilidade, fadiga e exaustão',
        ],
        'fonte' => $uol2020,
    ],
    [
        'titulo' => 'Depressão',
        'texto' => [
            'O sintoma clássico da depressão é a tristeza prolongada, por isso é comum as pessoas dizerem que estão deprimidas quando, na verdade, estão tristes porque algo de ruim aconteceu. “Mas na depressão, a tristeza é um sentimento constante, que se manifesta pela maior parte do dia, quase diariamente, e por um período mínimo de duas semanas”, especifica Antônio Geraldo da Silva, presidente eleito da Apal e diretor da ABP.',
            'Não existe um exame capaz de confirmar que alguém está deprimido. Por isso, o diagnóstico é clínico. Os sintomas da depressão podem variar de acordo com fatores como a gravidade do transtorno e a presença de condições associadas, como ansiedade, sintomas obsessivos ou psicóticos.',
        ],
        'fonte' => ['UOL VivaBem', 'https://www.uol.com.br/vivabem/noticias/redacao/2024/08/31/depressao-tem-sintomas-claros-veja-o-que-e-e-como-tratar.html'],
    ],
    [
        'titulo' => 'TOC',
        'texto' => [
            'O TOC é um transtorno de ansiedade caracterizado por pensamentos recorrentes e desagradáveis (obsessões) e comportamentos repetitivos ritualizados (compulsões), voltados para a redução do desconforto associado a tais pensamentos. Estudos estimam que no Brasil existem entre 3 a 4 milhões de pessoas com esse quadro.',
        ],
        'lista_titulo' => 'Categorias de sinais',
        'lista' => [
            'Excesso de limpeza',
            'Verificadores (alguém que confirma repetidamente as coisas)',
            'Duvidosos e pecadores (acreditam que tudo deve ser feito de maneira certa, pois caso contrário algo terrível pode acontecer como punição)',
            'Excesso de organização',
            'Acumuladores',
        ],
        'fonte' => $uol2020,
    ],
    [
        'titulo' => 'Transtorno alimentar',
        'texto' => [
            'O transtorno alimentar retrata o total descompasso ao ingerir alimentos ou, então, a perturbação psicológica de não comer. Trata-se de uma condição consideravelmente grave, que causa grande impacto na saúde de quem sofre da doença.',
            'É marcado por comportamentos alimentares temerários. Ou a pessoa deixa de comer, por estar complexada em relação ao seu peso, ou acaba ingerindo muitos alimentos por conta de um descontrole emocional. Sendo assim, a questão emocional é muito presente no distúrbio. Normalmente, essa instabilidade decorre de traumas desenvolvidos na adolescência, mas é possível que se apresente em outras idades.',
        ],
        'fonte' => ['Hospital Israelita Albert Einstein', 'https://vidasaudavel.einstein.br/transtorno-alimentar/'],
    ],
    [
        'titulo' => 'Somatização',
        'texto' => [
            'Somatização é quando a mente, por meio de pensamentos e do estado emocional em conflito, manifesta dores e doenças no corpo físico. Através das nossas condições psicológicas, o corpo pode responder apresentando um problema que até então não existia.',
        ],
        'lista_titulo' => 'Como a somatização pode afetar',
        'lista' => [
            'Dores e problemas nas articulações',
            'Dores no pescoço, sensação de enrijecimento até os ombros',
            'Queda no sistema imunológico',
            'Dores de cabeça',
            'Zumbido no ouvido',
            'Dificuldade para dormir',
            'Dificuldade para respirar',
            'Surgimento de doenças dermatológicas',
            'Enjoos e problemas no sistema digestivo',
        ],
    ],
];
?>

<main id="conteudo">
    <div class="container">
        <div class="page-head">
            <span class="eyebrow">Mental</span>
            <h1>Saúde mental</h1>
            <p class="lead">Conhecer os sinais é o primeiro passo para cuidar de si e de quem está por perto. Toque em um tema para ler mais.</p>
        </div>

        <div class="acordeoes">
            <?php foreach ($condicoes as $c): ?>
                <details class="acordeao" name="condicao">
                    <summary><?= e($c['titulo']) ?></summary>
                    <div class="acordeao__body">
                        <?php foreach ($c['texto'] as $paragrafo): ?>
                            <p><?= e($paragrafo) ?></p>
                        <?php endforeach; ?>

                        <?php if (!empty($c['lista'])): ?>
                            <h4><?= e($c['lista_titulo']) ?></h4>
                            <ul>
                                <?php foreach ($c['lista'] as $item): ?>
                                    <li><?= e($item) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (!empty($c['fonte'])): ?>
                            <p class="acordeao__fonte">Fonte: <a href="<?= e($c['fonte'][1]) ?>" target="_blank" rel="noopener"><?= e($c['fonte'][0]) ?></a></p>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>

        <section class="card importancia" style="margin-top: 2rem;">
            <h2>Importância da saúde mental</h2>
            <p>A saúde mental de uma pessoa está relacionada à forma como ela reage às exigências da vida e ao modo como harmoniza seus desejos, capacidades, ambições, ideias e emoções. Ter saúde mental é estar bem consigo mesmo e com os outros e aceitar as exigências da vida.</p>
            <p style="margin-bottom: 0;">Saber como cuidar da saúde mental é importante, sendo possível trabalhar com três pilares fundamentais: <strong>prevenção</strong>, <strong>percepção</strong> e <strong>tratamento</strong>.</p>
        </section>

        <aside class="card apoio" aria-labelledby="apoio-titulo">
            <div class="apoio__texto">
                <h3 id="apoio-titulo">Precisa conversar agora?</h3>
                <p>Ninguém precisa enfrentar um momento difícil sem apoio. O Centro de Valorização da Vida (CVV) atende de forma gratuita e sigilosa, 24 horas por dia. Em caso de emergência, ligue 192 (SAMU).</p>
            </div>
            <a class="apoio__tel" href="tel:188" aria-label="Ligar para o CVV, telefone 188">188<small>CVV · 24h · gratuito</small></a>
        </aside>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
