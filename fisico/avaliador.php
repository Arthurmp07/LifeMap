<?php
require_once __DIR__ . '/../includes/bootstrap.php';

exigir_papel('usuario');

// Dados que o assets/js/avaliador.js lê.
$configJs = [
    'api'       => url('fisico/avaliador_api.php'),
    'mediapipe' => [
        'base'   => asset('vendor/mediapipe'),
        'modelo' => asset('vendor/mediapipe/pose_landmarker_lite.task'),
    ],
    'contagem'  => 5,
];

$pageTitle = 'Avaliador de físico';
$paginaAtiva = 'fisico';
$bodyClass = 'tema-fisico';
$pageDescription = 'Abra a câmera, avalie sua postura e proporções e guarde a foto com o resultado no seu perfil.';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container">
        <div class="page-head">
            <p class="breadcrumb"><a href="<?= url('fisico/') ?>">← Físico</a></p>
            <span class="eyebrow">Físico</span>
            <h1>Avaliador de físico</h1>
            <p class="lead">Abra a câmera, tire uma foto de frente e veja a análise de postura e proporções. A foto e o resultado ficam guardados no seu perfil, para você acompanhar a evolução.</p>
        </div>

        <div class="avaliador">
            <section class="card" aria-labelledby="camera-titulo">
                <h2 id="camera-titulo" class="visually-hidden">Câmera</h2>

                <div class="palco" id="palco">
                    <video id="video" playsinline muted></video>
                    <img id="foto" alt="Foto capturada" hidden>
                    <canvas id="sobreposicao" aria-hidden="true"></canvas>
                    <div class="palco__contagem" id="contagem" aria-hidden="true" hidden></div>
                    <div class="palco__mensagem" id="palco-mensagem">
                        <ion-icon name="camera-outline" aria-hidden="true"></ion-icon>
                        <p id="palco-texto">Sua câmera ainda está desligada.</p>
                    </div>
                </div>

                <p class="palco__estado" id="estado" role="status" aria-live="polite" hidden></p>

                <div class="avaliador__acoes" id="acoes">
                    <button type="button" class="btn btn--primary" id="btn-abrir">
                        <ion-icon name="videocam-outline"></ion-icon> Abrir câmera
                    </button>
                    <button type="button" class="btn btn--primary" id="btn-contagem" hidden>Tirar foto em <?= $configJs['contagem'] ?> s</button>
                    <button type="button" class="btn btn--ghost" id="btn-agora" hidden>Tirar foto agora</button>
                    <button type="button" class="btn btn--ghost" id="btn-virar" hidden>Virar câmera</button>
                    <button type="button" class="btn btn--ghost" id="btn-fechar" hidden>Fechar câmera</button>
                    <button type="button" class="btn btn--ghost" id="btn-cancelar" hidden>Cancelar contagem</button>
                </div>
            </section>

            <aside class="card avaliador__dicas" aria-labelledby="dicas-titulo">
                <h2 id="dicas-titulo">Para uma boa avaliação</h2>
                <ul class="dicas">
                    <li>Fique <strong>de frente</strong> para a câmera, com o <strong>corpo inteiro</strong> à vista (a uns 2 ou 3 metros).</li>
                    <li>Fique em pé, relaxado, com os braços soltos ao lado do corpo e os pés na largura dos ombros.</li>
                    <li>Use roupas justas ou ajustadas e um lugar bem iluminado.</li>
                    <li>Repita sempre no <strong>mesmo lugar e enquadramento</strong> para comparar suas fotos.</li>
                </ul>
                <p class="nota">
                    <ion-icon name="lock-closed-outline" aria-hidden="true"></ion-icon>
                    A análise acontece no seu navegador. A foto só é enviada e guardada quando você clica em “Salvar no meu perfil”, e só você consegue vê-la.
                </p>
            </aside>
        </div>

        <section class="card" id="resultado" tabindex="-1" aria-labelledby="resultado-titulo" hidden>
            <h2 id="resultado-titulo">Resultado da avaliação</h2>
            <div id="resultado-corpo" aria-live="polite"></div>
        </section>

        <section class="card" id="historico" aria-labelledby="historico-titulo">
            <h2 id="historico-titulo">Minhas avaliações</h2>
            <div id="historico-corpo" aria-live="polite">
                <p class="hist__vazio">Carregando…</p>
            </div>
        </section>
    </div>
</main>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script type="application/json" id="avaliador-config"><?= json_encode($configJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="<?= asset('js/avaliador.js') ?>"></script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
