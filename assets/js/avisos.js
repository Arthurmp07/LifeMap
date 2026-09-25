// Avisos do atendimento em qualquer página:
//   - contador no menu (convites + mensagens não lidas) e o mesmo número no título da aba;
//   - aviso de chamada entrando (com Atender/Recusar), quando a pessoa está em outra página.
// Só pergunta ao servidor com a aba visível. Na conversa aberta, a própria página trata da chamada.
(function () {
    var configEl = document.getElementById('avisos-config');
    if (!configEl) return;
    var config = JSON.parse(configEl.textContent);
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    var INTERVALO = 12000;
    var INTERVALO_TOCANDO = 3000;
    var tituloBase = document.title;
    var timer = null;
    var parou = false;
    var faixa = null;          // aviso de chamada na tela
    var total = 0;

    function elemento(nome, classe, texto) {
        var no = document.createElement(nome);
        if (classe) no.className = classe;
        if (texto !== undefined) no.textContent = texto;
        return no;
    }

    function atualizarTitulo() {
        if (faixa) return;   // enquanto toca, o título mostra quem está ligando
        document.title = (total > 0 ? '(' + total + ') ' : '') + tituloBase;
    }

    function esconderChamada() {
        if (!faixa) return;
        faixa.remove();
        faixa = null;
        if (window.LifeMapToque) window.LifeMapToque.parar();
        atualizarTitulo();
    }

    function recusar(chamada) {
        var corpo = new URLSearchParams({ acao: 'encerrar', id: chamada.atendimento, chamada: chamada.id });
        fetch(config.chamadaApi, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf, 'Accept': 'application/json' }, body: corpo })
            .catch(function () { /* sem rede: a chamada se perde sozinha em 45 s */ });
        esconderChamada();
    }

    function mostrarChamada(chamada) {
        // Na própria conversa, a página já mostra a chamada (com mais recursos).
        if (!chamada || chamada.atendimento === config.aberto) { esconderChamada(); return; }
        if (faixa && faixa.getAttribute('data-chamada') === String(chamada.id)) return;

        esconderChamada();
        var tipo = chamada.video ? 'vídeo' : 'voz';
        faixa = elemento('div', 'chamada-aviso');
        faixa.setAttribute('role', 'alertdialog');
        faixa.setAttribute('aria-label', 'Chamada de ' + tipo + ' de ' + chamada.de);
        faixa.setAttribute('data-chamada', chamada.id);

        var info = elemento('div', 'chamada-aviso__info');
        info.append(elemento('strong', '', chamada.de), elemento('span', '', 'está ligando (' + tipo + ')'));

        var acoes = elemento('div', 'chamada-aviso__acoes');
        var atender = elemento('a', 'btn btn--primary btn--sm', 'Atender');
        atender.href = config.chat + '?id=' + chamada.atendimento + '#atender';
        var recusarBotao = elemento('button', 'btn btn--ghost btn--sm', 'Recusar');
        recusarBotao.type = 'button';
        recusarBotao.addEventListener('click', function () { recusar(chamada); });
        acoes.append(atender, recusarBotao);

        faixa.append(info, acoes);
        document.body.append(faixa);
        document.title = '📞 ' + chamada.de + ' está ligando';
        if (window.LifeMapToque) window.LifeMapToque.iniciar();
    }

    function mostrar(resumo) {
        total = resumo.total || 0;
        document.querySelectorAll('[data-aviso="atendimento"]').forEach(function (selo) {
            selo.textContent = total > 99 ? '99+' : String(total);
            selo.title = total + (total === 1 ? ' aviso' : ' avisos');
            selo.hidden = total <= 0;
        });
        mostrarChamada(resumo.chamada);
        atualizarTitulo();
    }

    function consultar() {
        if (parou || document.visibilityState !== 'visible') return;
        fetch(config.api + '?acao=resumo', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (resp) {
                if (resp.status === 401 || resp.status === 403) { parou = true; return null; }
                return resp.ok ? resp.json() : null;
            })
            .then(function (json) { if (json && json.resumo) mostrar(json.resumo); })
            .catch(function () { /* sem rede: tenta de novo no próximo ciclo */ })
            .then(agendar);
    }

    function agendar() {
        clearTimeout(timer);
        if (!parou) timer = setTimeout(consultar, faixa ? INTERVALO_TOCANDO : INTERVALO);
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') { clearTimeout(timer); consultar(); }
    });

    consultar();
})();
