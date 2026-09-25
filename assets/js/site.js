// Comportamentos comuns a todas as páginas.

// Menu responsivo
(function () {
    var botao = document.querySelector('.nav-toggle');
    var menu = document.getElementById('menu-principal');
    if (!botao || !menu) return;

    botao.addEventListener('click', function () {
        var aberto = menu.classList.toggle('is-open');
        botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
        botao.setAttribute('aria-label', aberto ? 'Fechar menu' : 'Abrir menu');
    });
})();

// Modo escuro: segue o sistema até a pessoa escolher; a escolha fica salva neste navegador.
(function () {
    var botao = document.getElementById('tema-toggle');
    if (!botao) return;

    var raiz = document.documentElement;
    var sistema = window.matchMedia('(prefers-color-scheme: dark)');

    function escuroAgora() {
        var escolhido = raiz.getAttribute('data-theme');
        return escolhido ? escolhido === 'dark' : sistema.matches;
    }

    function atualizar() {
        var escuro = escuroAgora();
        botao.setAttribute('aria-pressed', escuro ? 'true' : 'false');
        var icone = botao.querySelector('ion-icon');
        if (icone) icone.setAttribute('name', escuro ? 'sunny-outline' : 'moon-outline');
        var cor = document.querySelector('meta[name="theme-color"]');
        if (cor) cor.setAttribute('content', escuro ? '#0E1512' : '#2F7D6B');
    }

    botao.addEventListener('click', function () {
        var novo = escuroAgora() ? 'light' : 'dark';
        raiz.setAttribute('data-theme', novo);
        try { localStorage.setItem('lifemap-tema', novo); } catch (e) { /* sem armazenamento */ }
        atualizar();
    });

    if (sistema.addEventListener) sistema.addEventListener('change', atualizar);
    atualizar();
})();

// Formulários com data-confirmar="pergunta" pedem confirmação antes de enviar.
document.addEventListener('submit', function (ev) {
    var pergunta = ev.target.getAttribute && ev.target.getAttribute('data-confirmar');
    if (pergunta && !window.confirm(pergunta)) ev.preventDefault();
});

// Botões com data-copiar="#seletor" copiam o texto daquele elemento.
document.addEventListener('click', function (ev) {
    var botao = ev.target.closest && ev.target.closest('[data-copiar]');
    if (!botao) return;
    var alvo = document.querySelector(botao.getAttribute('data-copiar'));
    if (!alvo) return;
    var texto = alvo.textContent.trim();

    function avisar(ok) {
        var original = botao.getAttribute('data-original') || botao.innerHTML;
        botao.setAttribute('data-original', original);
        botao.textContent = ok ? 'Copiado!' : 'Selecione e copie (Ctrl+C)';
        setTimeout(function () { botao.innerHTML = original; }, 2000);
    }

    function copiaAntiga() {
        var faixa = document.createRange();
        faixa.selectNodeContents(alvo);
        var selecao = window.getSelection();
        selecao.removeAllRanges();
        selecao.addRange(faixa);
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { /* sem suporte */ }
        avisar(ok);
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(texto).then(function () { avisar(true); }, copiaAntiga);
    } else {
        copiaAntiga();
    }
});

// Máscara de telefone em campos com data-mascara="telefone":
// (11) 91234-5678 ou (11) 1234-5678
(function () {
    function formatar(valor) {
        var d = valor.replace(/\D/g, '').slice(0, 11);
        if (d.length === 0) return '';
        if (d.length <= 2) return '(' + d;
        if (d.length <= 6) return '(' + d.slice(0, 2) + ') ' + d.slice(2);
        if (d.length <= 10) return '(' + d.slice(0, 2) + ') ' + d.slice(2, 6) + '-' + d.slice(6);
        return '(' + d.slice(0, 2) + ') ' + d.slice(2, 7) + '-' + d.slice(7);
    }

    document.querySelectorAll('[data-mascara="telefone"]').forEach(function (campo) {
        campo.addEventListener('input', function () { campo.value = formatar(campo.value); });
    });
})();
