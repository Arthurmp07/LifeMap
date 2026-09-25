// Chat do atendimento. A configuração vem do PHP em <script id="chat-config" type="application/json">.
//
// Não há conexão aberta com o servidor: a página pergunta por mensagens novas a cada poucos segundos
// (mais devagar com a aba em segundo plano). O texto das mensagens entra sempre por textContent,
// nunca como HTML.
(function () {
    var config = JSON.parse(document.getElementById('chat-config').textContent);
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    var rolagem = document.getElementById('chat-mensagens');
    var lista = document.getElementById('chat-lista');
    var vazio = document.getElementById('chat-vazio');
    var botaoMais = document.getElementById('chat-mais');
    var botaoNovas = document.getElementById('chat-novas');
    var form = document.getElementById('chat-form');
    var campo = document.getElementById('chat-texto');
    var botaoEnviar = document.getElementById('chat-enviar');
    var contador = document.getElementById('chat-contador');
    var erro = document.getElementById('chat-erro');
    var avisoEncerrado = document.getElementById('chat-encerrado');

    var INTERVALO_VISIVEL = 2500;
    var INTERVALO_OCULTA = 10000;

    var mensagens = [];      // todas as mensagens carregadas, da mais antiga para a mais nova
    var ids = {};            // id -> true (evita desenhar duas vezes)
    var ultimoId = 0;
    var temMais = false;
    var ativo = config.ativo;
    var lidaAte = 0;         // maior id meu que a outra pessoa já leu
    var precisaMarcarLidas = false;
    var enviando = false;
    var parou = false;
    var falhas = 0;
    var timer = null;

    // ---------- utilidades ----------

    function dois(n) { return (n < 10 ? '0' : '') + n; }
    function hora(d) { return dois(d.getHours()) + ':' + dois(d.getMinutes()); }
    function chaveDoDia(d) { return d.getFullYear() + '-' + dois(d.getMonth() + 1) + '-' + dois(d.getDate()); }
    function rotuloDoDia(d) {
        var hoje = new Date();
        var ontem = new Date(); ontem.setDate(hoje.getDate() - 1);
        if (chaveDoDia(d) === chaveDoDia(hoje)) return 'Hoje';
        if (chaveDoDia(d) === chaveDoDia(ontem)) return 'Ontem';
        return dois(d.getDate()) + '/' + dois(d.getMonth() + 1) + '/' + d.getFullYear();
    }
    function elemento(nome, classe, texto) {
        var no = document.createElement(nome);
        if (classe) no.className = classe;
        if (texto !== undefined) no.textContent = texto;
        return no;
    }
    function visivel() { return document.visibilityState === 'visible'; }
    function pertoDoFim() { return rolagem.scrollHeight - rolagem.scrollTop - rolagem.clientHeight < 80; }
    function irParaOFim() { rolagem.scrollTop = rolagem.scrollHeight; botaoNovas.hidden = true; }

    // ---------- comunicação com a API ----------

    function pedir(acao, dados, post) {
        var opcoes = { credentials: 'same-origin', headers: { 'Accept': 'application/json' } };
        var url = config.api;
        var params = new URLSearchParams(Object.assign({ acao: acao, id: config.atendimento }, dados || {}));
        if (post) {
            opcoes.method = 'POST';
            opcoes.headers['X-CSRF-Token'] = csrf;
            opcoes.body = params;
        } else {
            url += '?' + params.toString();
        }
        return fetch(url, opcoes).then(function (resp) {
            return resp.json().catch(function () { return {}; }).then(function (json) {
                if (!resp.ok || json.ok === false) {
                    var e = new Error(json.mensagem || 'Não foi possível concluir agora.');
                    e.status = resp.status;
                    throw e;
                }
                return json;
            });
        });
    }

    // ---------- desenho ----------

    function itemDaMensagem(m) {
        var d = new Date(m.quando);
        if (m.de === 'sistema') {
            var aviso = elemento('li', 'msg msg--sistema');
            aviso.append(elemento('span', 'msg__sistema', m.texto));
            return aviso;
        }
        var li = elemento('li', 'msg msg--' + m.de);
        li.setAttribute('data-id', m.id);
        li.append(elemento('p', 'msg__texto', m.texto));
        var rodape = elemento('span', 'msg__hora', hora(d));
        rodape.title = rotuloDoDia(d) + ' às ' + hora(d);
        li.append(rodape);
        return li;
    }

    var diaDesenhado = '';
    function desenhar(novas) {
        novas.forEach(function (m) {
            var d = new Date(m.quando);
            var dia = chaveDoDia(d);
            if (dia !== diaDesenhado) {
                lista.append(elemento('li', 'chat__dia', rotuloDoDia(d)));
                diaDesenhado = dia;
            }
            lista.append(itemDaMensagem(m));
        });
        vazio.hidden = mensagens.length > 0;
        atualizarEstadoDasEnviadas();
    }

    function redesenharTudo() {
        lista.textContent = '';
        diaDesenhado = '';
        desenhar(mensagens);
    }

    // "Enviada" / "Lida" embaixo da última mensagem minha
    function atualizarEstadoDasEnviadas() {
        var antigo = lista.querySelector('.msg__estado');
        if (antigo) antigo.remove();
        var minhas = lista.querySelectorAll('.msg--eu');
        if (!minhas.length) return;
        var ultima = minhas[minhas.length - 1];
        var id = Number(ultima.getAttribute('data-id'));
        var lida = id <= lidaAte;
        var estado = elemento('span', 'msg__estado' + (lida ? ' msg__estado--lida' : ''), lida ? 'Lida' : 'Enviada');
        ultima.append(estado);
    }

    function atualizarEstadoDoAtendimento() {
        form.hidden = !ativo;
        avisoEncerrado.hidden = ativo;
        var acoes = document.getElementById('chat-acoes');
        if (acoes) acoes.classList.toggle('is-encerrado', !ativo);
        document.dispatchEvent(new CustomEvent('chat:estado', { detail: { ativo: ativo } }));
    }

    // Junta mensagens novas (ignora as que já existem) e desenha no fim.
    function receber(novas, minhaAcao) {
        var reais = novas.filter(function (m) { return !ids[m.id]; });
        if (!reais.length) return;
        var noFim = pertoDoFim();
        reais.forEach(function (m) {
            ids[m.id] = true;
            mensagens.push(m);
            if (m.id > ultimoId) ultimoId = m.id;
            if (m.de === 'outro') precisaMarcarLidas = true;
        });
        desenhar(reais);
        if (noFim || minhaAcao) irParaOFim();
        else botaoNovas.hidden = false;
        marcarLidasSeVisivel();
    }

    function marcarLidasSeVisivel() {
        if (!precisaMarcarLidas || !visivel()) return;
        precisaMarcarLidas = false;
        pedir('lidas', {}, true).catch(function () { precisaMarcarLidas = true; });
    }

    // ---------- carregamento e polling ----------

    function aplicarEstado(resposta) {
        var mudouLida = resposta.lida_ate !== lidaAte;
        lidaAte = resposta.lida_ate;
        if (mudouLida) atualizarEstadoDasEnviadas();
        if (resposta.ativo !== ativo) {
            ativo = resposta.ativo;
            atualizarEstadoDoAtendimento();
        }
    }

    function abrirConversa() {
        return pedir('mensagens').then(function (r) {
            temMais = r.tem_mais;
            botaoMais.hidden = !temMais;
            r.mensagens.forEach(function (m) {
                ids[m.id] = true;
                mensagens.push(m);
                if (m.id > ultimoId) ultimoId = m.id;
                if (m.de === 'outro') precisaMarcarLidas = true;
            });
            desenhar(mensagens);
            aplicarEstado(r);
            irParaOFim();
            marcarLidasSeVisivel();
        });
    }

    function buscarNovas() {
        if (parou) return;
        pedir('mensagens', { depois: ultimoId }).then(function (r) {
            falhas = 0;
            receber(r.mensagens, false);
            aplicarEstado(r);
        }).catch(function (e) {
            if (e.status === 401 || e.status === 403 || e.status === 404) {
                parou = true;
                mostrarErro('Você não tem mais acesso a esta conversa. Recarregue a página ou entre de novo.');
                return;
            }
            falhas++;
        }).then(agendar);
    }

    function agendar() {
        clearTimeout(timer);
        if (parou) return;
        var base = visivel() ? INTERVALO_VISIVEL : INTERVALO_OCULTA;
        timer = setTimeout(buscarNovas, base * Math.min(1 + falhas, 6));   // com falhas, espera cada vez mais
    }

    document.addEventListener('visibilitychange', function () {
        if (visivel()) {
            clearTimeout(timer);
            buscarNovas();
            marcarLidasSeVisivel();
        }
    });

    botaoMais.addEventListener('click', function () {
        if (!mensagens.length) return;
        botaoMais.disabled = true;
        pedir('mensagens', { antes: mensagens[0].id }).then(function (r) {
            var alturaAntes = rolagem.scrollHeight;
            r.mensagens.slice().reverse().forEach(function (m) {
                if (ids[m.id]) return;
                ids[m.id] = true;
                mensagens.unshift(m);
            });
            temMais = r.tem_mais;
            botaoMais.hidden = !temMais;
            redesenharTudo();
            rolagem.scrollTop = rolagem.scrollHeight - alturaAntes;   // mantém o ponto que a pessoa estava lendo
        }).catch(function (e) { mostrarErro(e.message); }).then(function () { botaoMais.disabled = false; });
    });

    botaoNovas.addEventListener('click', irParaOFim);
    rolagem.addEventListener('scroll', function () { if (pertoDoFim()) botaoNovas.hidden = true; });

    // ---------- envio ----------

    function mostrarErro(texto) {
        erro.textContent = texto;
        erro.hidden = false;
    }

    function ajustarCampo() {
        campo.style.height = 'auto';
        campo.style.height = Math.min(campo.scrollHeight, 160) + 'px';
        var n = campo.value.length;
        contador.hidden = n < config.max - 300;
        contador.textContent = n + ' / ' + config.max;
    }
    campo.addEventListener('input', ajustarCampo);

    // Enter envia; Shift+Enter quebra a linha (e não atrapalha quem digita com teclado de celular ou IME).
    campo.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter' && !ev.shiftKey && !ev.isComposing) {
            ev.preventDefault();
            if (form.requestSubmit) form.requestSubmit(); else botaoEnviar.click();
        }
    });

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var texto = campo.value.trim();
        if (!texto || enviando || !ativo) return;

        enviando = true;
        botaoEnviar.disabled = true;
        erro.hidden = true;
        pedir('enviar', { texto: texto }, true).then(function (r) {
            campo.value = '';
            ajustarCampo();
            receber([r.mensagem], true);
        }).catch(function (e) {
            mostrarErro(e.message);   // o texto continua no campo: nada se perde
            if (e.status === 409) { ativo = false; atualizarEstadoDoAtendimento(); }
        }).then(function () {
            enviando = false;
            botaoEnviar.disabled = false;
            campo.focus();
        });
    });

    // ---------- início ----------

    atualizarEstadoDoAtendimento();
    abrirConversa().catch(function (e) {
        parou = true;
        mostrarErro(e.message || 'Não foi possível abrir a conversa.');
    }).then(agendar);
})();
