<footer class="site-footer">
    <div class="container site-footer__inner">
        <div class="site-footer__brand">
            <img src="<?= asset('img/marca/logo-simbolo.png') ?>" alt="" width="64" height="64">
            <div>
                <strong>Life<span class="brand__map">Map</span></strong>
                <p>Seus dados. Sua evolução.</p>
            </div>
        </div>

        <nav class="site-footer__team" aria-label="Autoria">
            <span class="site-footer__label">Feito por</span>
            <ul>
                <li><a href="https://github.com/Arthurmp07" target="_blank" rel="noopener">Arthur Mello</a></li>
            </ul>
        </nav>
    </div>
    <p class="container site-footer__note">
        Conteúdo informativo. Não substitui a orientação de médicos, nutricionistas, educadores físicos ou psicólogos.
    </p>
</footer>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
<script src="<?= asset('js/site.js') ?>"></script>
<?php if (usuario_logado() && in_array(papel_da_sessao(), ['usuario', 'profissional'], true)): ?>
    <script type="application/json" id="avisos-config"><?= json_encode([
        'api'         => url('atendimento/api.php'),
        'chamadaApi'  => url('atendimento/chamada_api.php'),
        'chat'        => url('atendimento/chat.php'),
        'aberto'      => (int) ($atendimentoAberto ?? 0),   // conversa aberta nesta página (a chamada dela é tratada lá)
    ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script src="<?= asset('js/toque.js') ?>"></script>
    <script src="<?= asset('js/avisos.js') ?>"></script>
<?php endif; ?>
</body>
</html>
