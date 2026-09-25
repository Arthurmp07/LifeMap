<?php
// Conversa de um atendimento (vista pelo usuário e pelo profissional).
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/chat.php';

$eu = exigir_papel('usuario', 'profissional');
$euId = (int) $eu['id'];
$ehProfissional = $eu['papel'] === 'profissional';

$atendimento = atendimento_do_participante((int) ($_GET['id'] ?? 0), $euId);
$conversaExiste = $atendimento && in_array($atendimento['status'], ['ativo', 'encerrado'], true);
$voltar = $ehProfissional ? url('profissional/') : url('atendimento/');

if (!$conversaExiste) {
    http_response_code(404);
    $pageTitle = 'Conversa não encontrada';
    $paginaAtiva = $ehProfissional ? 'pacientes' : 'atendimento';
    require __DIR__ . '/../partials/head.php';
    require __DIR__ . '/../partials/header.php';
    ?>
    <main id="conteudo">
        <div class="container auth">
            <section class="card auth__card">
                <h1>Conversa não encontrada</h1>
                <p class="auth__lead">Esta conversa não existe ou ainda não começou (o convite precisa ser aceito).</p>
                <a class="btn btn--primary btn--lg btn--block" href="<?= e($voltar) ?>">Voltar</a>
            </section>
        </div>
    </main>
    <?php
    require __DIR__ . '/../partials/footer.php';
    exit();
}

$outro = outro_lado_do_atendimento($atendimento, $euId);
$ativo = $atendimento['status'] === 'ativo' && $atendimento['profissional_ativo'] && $atendimento['usuario_ativo'];

$partesNome = explode(' ', nome_de_saudacao($outro['nome']));
$inicial = mb_strtoupper(mb_substr(end($partesNome), 0, 1));

$atendimentoAberto = (int) $atendimento['id'];   // o rodapé usa: o aviso de chamada não repete o que esta página já mostra

$configJs = [
    'atendimento' => (int) $atendimento['id'],
    'api'         => url('atendimento/api.php'),
    'ativo'       => $ativo,
    'nomeOutro'   => $outro['nome'],
    'max'         => CHAT_TEXTO_MAX,
];

$chamadaJs = [
    'api'         => url('atendimento/chamada_api.php'),
    'atendimento' => (int) $atendimento['id'],
    'nomeOutro'   => $outro['nome'],
    'ativo'       => $ativo,
    'iceServers'  => (require BASE_PATH . '/config/webrtc.php')['iceServers'],
];

$pageTitle = 'Conversa com ' . $outro['nome'];
$paginaAtiva = $ehProfissional ? 'pacientes' : 'atendimento';
$pageDescription = 'Conversa do atendimento.';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container chat-pagina">
        <p class="breadcrumb"><a href="<?= e($voltar) ?>">← <?= $ehProfissional ? 'Pacientes' : 'Atendimento' ?></a></p>

        <section class="card chat" aria-labelledby="chat-titulo">
            <header class="chat__topo">
                <span class="chat__avatar" aria-hidden="true"><?= e($inicial) ?></span>
                <div class="chat__quem">
                    <h1 id="chat-titulo"><?= e($outro['nome']) ?></h1>
                    <p class="nota"><?= e($outro['detalhe']) ?></p>
                </div>
                <div class="chat__acoes" id="chat-acoes">
                    <?php if ($ehProfissional && $ativo): ?>
                        <a class="btn btn--ghost btn--sm" href="<?= url('profissional/paciente.php?id=' . (int) $atendimento['usuario_id']) ?>">Ver ficha</a>
                    <?php endif; ?>
                    <?php if ($ativo): ?>
                        <button type="button" class="btn btn--ghost btn--sm" id="ligar-video" title="Chamada de vídeo">
                            <ion-icon name="videocam-outline" aria-hidden="true"></ion-icon> Vídeo
                        </button>
                        <button type="button" class="btn btn--ghost btn--sm" id="ligar-voz" title="Chamada de voz">
                            <ion-icon name="call-outline" aria-hidden="true"></ion-icon> Voz
                        </button>
                    <?php endif; ?>
                </div>
            </header>

            <section class="chamada" id="chamada" aria-label="Chamada" hidden>
                <div class="chamada__palco" id="chamada-palco">
                    <video class="chamada__remoto" id="chamada-remoto" autoplay playsinline></video>
                    <div class="chamada__voz" id="chamada-voz">
                        <span class="chat__avatar chamada__avatar" aria-hidden="true"><?= e($inicial) ?></span>
                        <strong><?= e($outro['nome']) ?></strong>
                    </div>
                    <video class="chamada__local" id="chamada-local" autoplay playsinline muted aria-label="Sua imagem"></video>
                    <p class="chamada__estado" id="chamada-estado" role="status" aria-live="polite"></p>
                    <button type="button" class="chamada__som" id="chamada-som" hidden>Toque para ativar o som</button>
                </div>
                <div class="chamada__controles">
                    <button type="button" class="chamada__botao chamada__botao--atender" id="chamada-atender" hidden>
                        <ion-icon name="call" aria-hidden="true"></ion-icon> <span>Atender</span>
                    </button>
                    <button type="button" class="chamada__botao" id="chamada-mudo" aria-pressed="false" aria-label="Silenciar microfone" hidden>
                        <ion-icon name="mic-outline" aria-hidden="true"></ion-icon>
                    </button>
                    <button type="button" class="chamada__botao" id="chamada-camera" aria-pressed="false" aria-label="Desligar câmera" hidden>
                        <ion-icon name="videocam-outline" aria-hidden="true"></ion-icon>
                    </button>
                    <button type="button" class="chamada__botao chamada__botao--desligar" id="chamada-desligar" aria-label="Desligar">
                        <ion-icon name="call" aria-hidden="true"></ion-icon> <span>Desligar</span>
                    </button>
                </div>
            </section>
            <p class="chamada__aviso" id="chamada-aviso" role="alert" hidden></p>

            <div class="chat__aviso" id="chat-encerrado"<?= $ativo ? ' hidden' : '' ?>>
                Atendimento encerrado. A conversa ficou guardada só para leitura.
            </div>

            <div class="chat__mensagens" id="chat-mensagens" role="log" aria-live="polite" aria-relevant="additions" aria-label="Mensagens da conversa" tabindex="0">
                <button type="button" class="btn btn--ghost btn--sm chat__mais" id="chat-mais" hidden>Carregar mensagens anteriores</button>
                <ol class="chat__lista" id="chat-lista"></ol>
                <p class="chat__vazio" id="chat-vazio" hidden>Ainda não há mensagens. Escreva a primeira!</p>
            </div>
            <button type="button" class="btn btn--primary btn--sm chat__novas" id="chat-novas" hidden>Novas mensagens ↓</button>

            <form class="chat__form" id="chat-form"<?= $ativo ? '' : ' hidden' ?>>
                <label class="visually-hidden" for="chat-texto">Mensagem</label>
                <textarea class="input chat__texto" id="chat-texto" rows="1" maxlength="<?= CHAT_TEXTO_MAX ?>"
                          placeholder="Escreva uma mensagem…" autocomplete="off"></textarea>
                <button type="submit" class="btn btn--primary chat__enviar" id="chat-enviar" aria-label="Enviar mensagem">
                    <ion-icon name="send" aria-hidden="true"></ion-icon> <span class="chat__enviar-texto">Enviar</span>
                </button>
                <p class="chat__contador nota" id="chat-contador" hidden></p>
                <p class="chat__erro" id="chat-erro" role="alert" hidden></p>
            </form>
        </section>
    </div>
</main>

<script type="application/json" id="chat-config"><?= json_encode($configJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script type="application/json" id="chamada-config"><?= json_encode($chamadaJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="<?= asset('js/chat.js') ?>"></script>
<script src="<?= asset('js/chamada.js') ?>"></script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
