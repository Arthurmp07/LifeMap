// Chamadas de voz e vídeo do atendimento (WebRTC). A configuração vem do PHP em
// <script id="chamada-config" type="application/json">.
//
// O áudio e o vídeo vão direto de um navegador ao outro. O servidor do site só guarda o estado da
// chamada e os "sinais" (oferta, resposta e candidatos ICE) que os dois lados trocam para se conectar,
// e a página os busca por polling, do mesmo jeito que o chat.
//
// Fluxo de quem liga:   captura mídia -> "iniciar" -> cria a conexão -> envia a oferta -> recebe a resposta.
// Fluxo de quem atende: recebe o aviso -> captura mídia -> "atender" -> recebe a oferta -> envia a resposta.
(function () {
    var configEl = document.getElementById('chamada-config');
    if (!configEl) return;
    var config = JSON.parse(configEl.textContent);
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    var el = {
        painel: document.getElementById('chamada'),
        palco: document.getElementById('chamada-palco'),
        remoto: document.getElementById('chamada-remoto'),
        local: document.getElementById('chamada-local'),
        estado: document.getElementById('chamada-estado'),
        voz: document.getElementById('chamada-voz'),
        atender: document.getElementById('chamada-atender'),
        mudo: document.getElementById('chamada-mudo'),
        camera: document.getElementById('chamada-camera'),
        desligar: document.getElementById('chamada-desligar'),
        som: document.getElementById('chamada-som'),
        ligarVideo: document.getElementById('ligar-video'),
        ligarVoz: document.getElementById('ligar-voz'),
        aviso: document.getElementById('chamada-aviso'),
    };
    var rotuloDesligar = el.desligar.querySelector('span');
    var tituloBase = document.title;

    // ocioso | preparando | chamando | tocando | conectando | em_chamada
    var estado = 'ocioso';
    var chamada = null;             // {id, status, video, iniciador}
    var midia = null;               // MediaStream local
    var pc = null;
    var pcPronta = null;            // promessa: a conexão existe (os sinais esperam por ela)
    var remotoStream = null;
    var ultimoSinal = 0;
    var candidatosPendentes = [];
    var remotaDefinida = false;
    var fila = Promise.resolve();   // processa os sinais um por vez, na ordem
    var timerConsulta = null;
    var timerRelogio = null;
    var timerQueda = null;
    var inicioDaConversa = 0;
    var consultando = false;
    var ativo = config.ativo;

    // ---------- utilidades ----------

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

    function pode() {
        return window.isSecureContext && navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.RTCPeerConnection;
    }

    function dois(n) { return (n < 10 ? '0' : '') + n; }

    function texto(msg) { el.estado.textContent = msg; }

    function avisar(msg) {
        el.aviso.textContent = msg;
        el.aviso.hidden = !msg;
    }

    function toque(ligar) {
        var t = window.LifeMapToque;
        if (!t) return;
        if (ligar) t.iniciar(); else t.parar();
    }

    function mensagemDeMidia(e) {
        var nome = e && e.name;
        if (nome === 'NotAllowedError' || nome === 'SecurityError') return 'Permita o uso do microfone (e da câmera) no navegador para usar a chamada.';
        if (nome === 'NotFoundError' || nome === 'OverconstrainedError') return 'Não encontrei microfone ou câmera neste aparelho.';
        if (nome === 'NotReadableError' || nome === 'AbortError') return 'O microfone ou a câmera está sendo usado por outro programa.';
        return 'Não foi possível abrir o microfone ou a câmera.';
    }

    function capturar(comVideo) {
        return navigator.mediaDevices.getUserMedia({
            audio: { echoCancellation: true, noiseSuppression: true },
            video: comVideo ? { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' } : false
        });
    }

    // ---------- interface ----------

    // Botões e textos de cada momento da chamada.
    function desenhar() {
        var emChamada = estado !== 'ocioso';
        el.painel.hidden = !emChamada;
        el.painel.setAttribute('data-estado', estado);
        var video = !!(chamada && chamada.video);
        el.painel.classList.toggle('chamada--voz', !video);

        var tocando = estado === 'tocando';
        el.atender.hidden = !tocando;
        el.mudo.hidden = !midia;
        el.camera.hidden = !(midia && midia.getVideoTracks().length);
        rotuloDesligar.textContent = tocando ? 'Recusar' : (estado === 'chamando' || estado === 'preparando' ? 'Cancelar' : 'Desligar');
        el.desligar.setAttribute('aria-label', rotuloDesligar.textContent);

        var livre = !emChamada && ativo;
        // Durante a chamada os botões de ligar somem (não fazem sentido ali); sem atendimento ativo também.
        [el.ligarVideo, el.ligarVoz].forEach(function (b) { if (b) b.hidden = !livre; });
    }

    function mostrarVideoRemoto(sim) {
        el.painel.classList.toggle('chamada--com-imagem', sim);
    }

    // ---------- conexão WebRTC ----------

    function criarConexao() {
        pc = new RTCPeerConnection({ iceServers: config.iceServers || [] });
        remotoStream = new MediaStream();
        el.remoto.srcObject = remotoStream;

        midia.getTracks().forEach(function (faixa) { pc.addTrack(faixa, midia); });

        pc.ontrack = function (ev) {
            remotoStream.addTrack(ev.track);
            if (ev.track.kind === 'video') {
                mostrarVideoRemoto(true);
            }
            var tocar = el.remoto.play();
            if (tocar && tocar.catch) tocar.catch(function () { el.som.hidden = false; });   // o navegador pede um toque para liberar o som
        };

        pc.onicecandidate = function (ev) {
            if (ev.candidate) enviarSinal('ice', ev.candidate.toJSON());
        };

        pc.onconnectionstatechange = function () {
            var s = pc ? pc.connectionState : '';
            if (s === 'connected') {
                clearTimeout(timerQueda);
                if (estado !== 'em_chamada') {
                    estado = 'em_chamada';
                    inicioDaConversa = Date.now();
                    iniciarRelogio();
                    desenhar();
                }
            } else if (s === 'disconnected') {
                // Quedas curtas se recuperam sozinhas; se não voltar em 8 s, encerra.
                clearTimeout(timerQueda);
                texto('Conexão instável…');
                timerQueda = setTimeout(function () {
                    if (pc && pc.connectionState !== 'connected') falhar('A conexão caiu. Tente ligar de novo.');
                }, 8000);
            } else if (s === 'failed') {
                falhar('Não foi possível conectar. Verifique a internet dos dois lados e tente de novo.');
            }
        };
    }

    function enviarSinal(tipo, dados) {
        if (!chamada) return Promise.resolve();
        return pedir('sinal', { chamada: chamada.id, tipo: tipo, dados: JSON.stringify(dados) }, true).catch(function (e) {
            if (e.status === 409 || e.status === 404) return;   // a chamada acabou no meio do caminho: a consulta cuida do resto
            if (e.status === 429) falhar('Muitas tentativas de conexão. Tente ligar de novo.');
        });
    }

    function aplicarCandidatosPendentes() {
        var lista = candidatosPendentes;
        candidatosPendentes = [];
        lista.forEach(function (c) { pc.addIceCandidate(c).catch(function () { /* candidato inútil: ignora */ }); });
    }

    function tratarSinal(s) {
        return pcPronta.then(function () {
            if (!pc) return;
            if (s.tipo === 'offer') {
                return pc.setRemoteDescription(s.dados).then(function () {
                    remotaDefinida = true;
                    aplicarCandidatosPendentes();
                    return pc.createAnswer();
                }).then(function (resposta) {
                    return pc.setLocalDescription(resposta);
                }).then(function () {
                    return enviarSinal('answer', pc.localDescription.toJSON());
                });
            }
            if (s.tipo === 'answer') {
                return pc.setRemoteDescription(s.dados).then(function () {
                    remotaDefinida = true;
                    aplicarCandidatosPendentes();
                });
            }
            if (s.tipo === 'ice') {
                if (remotaDefinida) pc.addIceCandidate(s.dados).catch(function () { /* ignora */ });
                else candidatosPendentes.push(s.dados);
            }
        }).catch(function () { falhar('Não foi possível estabelecer a chamada. Tente de novo.'); });
    }

    // ---------- ligar, atender, desligar ----------

    function ligar(comVideo) {
        if (estado !== 'ocioso' || !ativo) return;
        avisar('');
        if (!pode()) {
            avisar('Para usar chamadas, abra o site por HTTPS (ou localhost) em um navegador com microfone' + (comVideo ? ' e câmera' : '') + '.');
            return;
        }

        estado = 'preparando';
        chamada = { id: 0, video: comVideo, iniciador: 'eu' };
        desenhar();
        texto('Abrindo ' + (comVideo ? 'câmera e ' : '') + 'microfone…');

        capturar(comVideo).then(function (stream) {
            midia = stream;
            el.local.srcObject = stream;
            return pedir('iniciar', { video: comVideo ? 1 : 0 }, true);
        }).then(function (r) {
            chamada = r.chamada;
            estado = 'chamando';
            ultimoSinal = 0;
            texto('Chamando ' + config.nomeOutro + '…');
            desenhar();
            var resolver;
            pcPronta = new Promise(function (ok) { resolver = ok; });
            criarConexao();
            resolver();
            agendarConsulta(true);
            return pc.createOffer();
        }).then(function (oferta) {
            return pc.setLocalDescription(oferta);
        }).then(function () {
            return enviarSinal('offer', pc.localDescription.toJSON());
        }).catch(function (e) {
            var deMidia = !e.status && !chamada.id;   // falhou antes de existir chamada no servidor: foi a câmera/microfone
            if (chamada && chamada.id) pedir('encerrar', { chamada: chamada.id }, true).catch(function () {});
            fim(deMidia ? mensagemDeMidia(e) : (e.message || 'Não foi possível ligar agora.'), true);
        });
    }

    function atender() {
        if (estado !== 'tocando' || !chamada) return;
        toque(false);
        estado = 'conectando';
        avisar('');
        desenhar();
        texto('Atendendo…');

        // Se a câmera falhar numa chamada de vídeo, atende só com áudio em vez de perder a chamada.
        var captura = capturar(chamada.video).catch(function (e) {
            if (!chamada.video) throw e;
            avisar('Não consegui abrir a câmera: você está atendendo só com áudio. ' + mensagemDeMidia(e));
            return capturar(false);
        });

        captura.then(function (stream) {
            midia = stream;
            el.local.srcObject = stream;
            return pedir('atender', { chamada: chamada.id }, true);
        }).then(function () {
            ultimoSinal = 0;
            var resolver;
            pcPronta = new Promise(function (ok) { resolver = ok; });
            criarConexao();
            resolver();
            desenhar();
            texto('Conectando…');
            agendarConsulta(true);
        }).catch(function (e) {
            var deMidia = !e.status;
            if (deMidia) {
                // Sem microfone não há como atender: recusa para quem ligou não ficar esperando.
                pedir('encerrar', { chamada: chamada.id }, true).catch(function () {});
                fim(mensagemDeMidia(e), true);
            } else {
                fim(e.message, true);
            }
        });
    }

    function desligar() {
        if (estado === 'ocioso' || !chamada) return;
        var id = chamada.id;
        if (id) pedir('encerrar', { chamada: id }, true).catch(function () { /* já tinha terminado */ });
        fim('', false);
    }

    function falhar(msg) {
        if (estado === 'ocioso') return;
        if (chamada && chamada.id) pedir('encerrar', { chamada: chamada.id }, true).catch(function () {});
        fim(msg, true);
    }

    // Limpa tudo e volta ao estado "ocioso". Com $mensagem, deixa o aviso na tela.
    function fim(mensagem, erro) {
        clearTimeout(timerConsulta);
        clearInterval(timerRelogio);
        clearTimeout(timerQueda);
        toque(false);
        if (pc) {
            pc.ontrack = pc.onicecandidate = pc.onconnectionstatechange = null;
            try { pc.close(); } catch (e) { /* já fechada */ }
        }
        if (midia) midia.getTracks().forEach(function (t) { t.stop(); });
        el.remoto.srcObject = null;
        el.local.srcObject = null;
        pc = null; pcPronta = null; midia = null; remotoStream = null; chamada = null;
        candidatosPendentes = []; remotaDefinida = false; ultimoSinal = 0; fila = Promise.resolve();
        estado = 'ocioso';
        mostrarVideoRemoto(false);
        el.som.hidden = true;
        el.mudo.setAttribute('aria-pressed', 'false');
        el.camera.setAttribute('aria-pressed', 'false');
        document.title = tituloBase;
        avisar(mensagem || '');
        el.aviso.classList.toggle('is-erro', !!erro);
        desenhar();
        agendarConsulta(false);
    }

    function iniciarRelogio() {
        clearInterval(timerRelogio);
        function atualizar() {
            var s = Math.floor((Date.now() - inicioDaConversa) / 1000);
            var h = Math.floor(s / 3600);
            texto('Em chamada · ' + (h ? h + ':' + dois(Math.floor(s % 3600 / 60)) : dois(Math.floor(s / 60))) + ':' + dois(s % 60));
        }
        atualizar();
        timerRelogio = setInterval(atualizar, 1000);
    }

    // ---------- consulta ao servidor ----------

    function motivoDoFim(c) {
        var quem = config.nomeOutro;
        if (c.status === 'recusada') return c.iniciador === 'eu' ? quem + ' recusou a chamada.' : '';
        if (c.status === 'perdida') return c.iniciador === 'eu' ? 'Ninguém atendeu a chamada.' : 'Chamada perdida.';
        if (c.status === 'cancelada') return c.iniciador === 'eu' ? '' : quem + ' cancelou a chamada.';
        if (c.status === 'encerrada') return 'Chamada encerrada.';
        return '';
    }

    function consultar() {
        if (consultando) return;
        // Ainda criando a chamada (abrindo câmera/microfone ou aguardando o servidor): não há o que consultar.
        if (estado === 'preparando' || (chamada && !chamada.id)) { agendarConsulta(false); return; }
        consultando = true;
        pedir('estado', { chamada: chamada && chamada.id ? chamada.id : 0, depois: ultimoSinal }).then(function (r) {
            var c = r.chamada;

            if (estado === 'ocioso') {
                if (c && c.status === 'tocando' && c.iniciador === 'outro') {
                    chamada = c;
                    estado = 'tocando';
                    texto(config.nomeOutro + ' está ligando (' + (c.video ? 'vídeo' : 'voz') + ')…');
                    document.title = '📞 ' + config.nomeOutro + ' está ligando';
                    toque(true);
                    desenhar();
                    if (location.hash === '#atender') {
                        history.replaceState(null, '', location.pathname + location.search);
                        atender();
                    }
                } else if (location.hash === '#atender') {
                    history.replaceState(null, '', location.pathname + location.search);   // a chamada já acabou
                } else if (c && c.status === 'em_andamento') {
                    // A página foi recarregada no meio de uma chamada: a mídia não volta sozinha. Encerra.
                    pedir('encerrar', { chamada: c.id }, true).catch(function () {});
                    avisar('A chamada foi encerrada porque a página foi recarregada.');
                    el.aviso.classList.add('is-erro');
                }
                return;
            }

            // Estou numa chamada (ligando, tocando, conectando ou conversando).
            if (!c || (chamada && chamada.id && c.id !== chamada.id)) {
                fim('A chamada terminou.', false);
                return;
            }
            chamada = c;

            if (c.status === 'tocando' || c.status === 'em_andamento') {
                if (c.status === 'em_andamento' && estado === 'chamando') {
                    estado = 'conectando';
                    texto('Conectando…');
                    desenhar();
                }
                // Sinais só são consumidos com a conexão pronta (quem foi chamado só a cria ao atender).
                if (pcPronta) {
                    r.sinais.forEach(function (s) {
                        if (s.id > ultimoSinal) ultimoSinal = s.id;
                        fila = fila.then(function () { return tratarSinal(s); });
                    });
                }
                return;
            }

            // Terminou (recusada, perdida, cancelada, encerrada) por quem estava do outro lado ou por tempo.
            fim(motivoDoFim(c), c.status === 'perdida' || c.status === 'recusada');
        }).catch(function (e) {
            if (e.status === 404 || e.status === 403 || e.status === 401) {
                if (estado !== 'ocioso') fim('Não foi possível continuar a chamada.', true);
                return 'parar';
            }
        }).then(function (parar) {
            consultando = false;
            if (parar !== 'parar') agendarConsulta(false);
        });
    }

    // Consulta mais rápida durante a chamada (troca de sinais), mais calma quando ninguém está ligando.
    function agendarConsulta(agora) {
        clearTimeout(timerConsulta);
        var espera;
        if (estado === 'ocioso') espera = document.visibilityState === 'visible' ? 2500 : 8000;
        else if (estado === 'tocando') espera = 1500;
        else espera = 1000;
        timerConsulta = setTimeout(consultar, agora ? 0 : espera);
    }

    // ---------- controles ----------

    el.ligarVideo && el.ligarVideo.addEventListener('click', function () { ligar(true); });
    el.ligarVoz && el.ligarVoz.addEventListener('click', function () { ligar(false); });
    el.atender.addEventListener('click', atender);
    el.desligar.addEventListener('click', desligar);

    el.mudo.addEventListener('click', function () {
        if (!midia) return;
        var mudar = el.mudo.getAttribute('aria-pressed') !== 'true';
        midia.getAudioTracks().forEach(function (t) { t.enabled = !mudar; });
        el.mudo.setAttribute('aria-pressed', mudar ? 'true' : 'false');
        el.mudo.setAttribute('aria-label', mudar ? 'Ativar microfone' : 'Silenciar microfone');
        el.mudo.querySelector('ion-icon').setAttribute('name', mudar ? 'mic-off-outline' : 'mic-outline');
    });

    el.camera.addEventListener('click', function () {
        if (!midia) return;
        var desligar = el.camera.getAttribute('aria-pressed') !== 'true';
        midia.getVideoTracks().forEach(function (t) { t.enabled = !desligar; });
        el.camera.setAttribute('aria-pressed', desligar ? 'true' : 'false');
        el.camera.setAttribute('aria-label', desligar ? 'Ligar câmera' : 'Desligar câmera');
        el.camera.querySelector('ion-icon').setAttribute('name', desligar ? 'videocam-off-outline' : 'videocam-outline');
        el.local.classList.toggle('is-oculto', desligar);
    });

    el.som.addEventListener('click', function () {
        el.remoto.play().then(function () { el.som.hidden = true; }).catch(function () {});
    });

    // O atendimento foi encerrado (o chat avisa): desliga qualquer chamada e esconde os botões.
    document.addEventListener('chat:estado', function (ev) {
        ativo = ev.detail.ativo;
        if (!ativo && estado !== 'ocioso') fim('O atendimento foi encerrado.', false);
        desenhar();
    });

    // Fechar ou recarregar a página no meio da chamada: avisa o servidor (o outro lado não fica esperando).
    window.addEventListener('pagehide', function () {
        if (estado === 'ocioso' || !chamada || !chamada.id || !navigator.sendBeacon) return;
        var dados = new FormData();
        dados.append('acao', 'encerrar');
        dados.append('id', config.atendimento);
        dados.append('chamada', chamada.id);
        dados.append('csrf', csrf);
        navigator.sendBeacon(config.api, dados);
    });

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') agendarConsulta(true);
    });

    // Teclado: Esc não desliga (evita desligar sem querer); os botões têm nomes acessíveis.

    desenhar();
    agendarConsulta(true);
})();
