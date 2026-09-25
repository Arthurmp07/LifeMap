// Avaliador de físico: câmera, detecção de pose (MediaPipe, roda no navegador),
// medidas de postura/proporção e histórico. As medidas são calculadas aqui;
// o texto da interpretação vem do servidor (fisico/avaliador_api.php).
(function () {
    'use strict';

    var config = JSON.parse(document.getElementById('avaliador-config').textContent);
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function porId(id) { return document.getElementById(id); }
    function elemento(nome, classe, texto) {
        var no = document.createElement(nome);
        if (classe) no.className = classe;
        if (texto !== undefined) no.textContent = texto;
        return no;
    }
    function dois(n) { return (n < 10 ? '0' : '') + n; }
    function dataHora(iso) {
        var d = new Date(iso);
        return dois(d.getDate()) + '/' + dois(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + dois(d.getHours()) + ':' + dois(d.getMinutes());
    }

    var el = {
        palco: porId('palco'), video: porId('video'), foto: porId('foto'), sobreposicao: porId('sobreposicao'),
        contagem: porId('contagem'), mensagem: porId('palco-mensagem'), mensagemTexto: porId('palco-texto'),
        estado: porId('estado'),
        abrir: porId('btn-abrir'), iniciarContagem: porId('btn-contagem'), agora: porId('btn-agora'),
        virar: porId('btn-virar'), fechar: porId('btn-fechar'), cancelar: porId('btn-cancelar'),
        resultado: porId('resultado'), resultadoCorpo: porId('resultado-corpo'),
        historico: porId('historico-corpo'), toast: porId('toast')
    };

    // Pontos do MediaPipe (lados da PESSOA: "esquerdo" aparece à direita da imagem).
    var NARIZ = 0, OMBRO_E = 11, OMBRO_D = 12, QUADRIL_E = 23, QUADRIL_D = 24, TORNOZELO_E = 27, TORNOZELO_D = 28;

    var s = {
        fase: 'ocioso',           // ocioso | carregando | camera | contagem | analisando | resultado
        stream: null,
        facing: 'user',
        temVariasCameras: false,
        landmarker: null,
        modo: null,
        carregandoModelo: null,
        conexoes: [],
        raf: 0,
        ultimoQuadro: 0,
        pausado: false,
        timerContagem: null,
        captura: null,            // { blob, url, medidas, pontos, largura, altura }
        ultimaOrientacao: null
    };

    // ---------- avisos ----------
    var timerToast;
    function toast(mensagem, erro) {
        el.toast.textContent = mensagem;
        el.toast.classList.toggle('toast--erro', !!erro);
        el.toast.classList.add('is-visible');
        clearTimeout(timerToast);
        timerToast = setTimeout(function () { el.toast.classList.remove('is-visible'); }, 5000);
    }

    // ---------- API ----------
    function api(acao, dados, arquivo) {
        var opcoes = { headers: { 'X-CSRF-Token': csrf } };
        var url = config.api;
        if (acao === 'listar') {
            url += '?acao=listar';
        } else {
            var corpo = new FormData();
            corpo.append('acao', acao);
            Object.keys(dados || {}).forEach(function (k) { corpo.append(k, dados[k]); });
            if (arquivo) corpo.append('foto', arquivo, 'foto.jpg');
            opcoes.method = 'POST';
            opcoes.body = corpo;
        }
        return fetch(url, opcoes).then(function (r) {
            return r.json().then(function (json) { return { status: r.status, json: json }; });
        });
    }

    // ---------- interface por fase ----------
    function mostrar(botao, visivel) { botao.hidden = !visivel; }

    function irPara(fase) {
        s.fase = fase;
        var camera = fase === 'camera', contagem = fase === 'contagem';
        mostrar(el.abrir, fase === 'ocioso' || fase === 'resultado');
        el.abrir.lastChild.textContent = fase === 'resultado' ? ' Tirar outra foto' : ' Abrir câmera';
        mostrar(el.iniciarContagem, camera);
        mostrar(el.agora, camera);
        mostrar(el.virar, camera && s.temVariasCameras);
        mostrar(el.fechar, camera);
        mostrar(el.cancelar, contagem);

        var mostrandoFoto = fase === 'analisando' || fase === 'resultado';
        el.video.hidden = mostrandoFoto || !(camera || contagem);
        el.foto.hidden = !mostrandoFoto;
        el.mensagem.hidden = camera || contagem || mostrandoFoto;
        el.palco.classList.toggle('palco--espelho', (camera || contagem) && s.facing === 'user');
        if (!(camera || contagem)) el.estado.hidden = true;
        if (!contagem) el.contagem.hidden = true;
        if (fase === 'ocioso') limparSobreposicao();
    }

    function textoDoPalco(texto) { el.mensagemTexto.textContent = texto; }

    function orientar(nivel, texto) {
        el.estado.hidden = false;
        el.estado.textContent = texto;
        el.estado.className = 'palco__estado palco__estado--' + nivel;
    }

    // ---------- modelo (MediaPipe) ----------
    function carregarModelo() {
        if (s.carregandoModelo) return s.carregandoModelo;
        s.carregandoModelo = import(config.mediapipe.base + '/vision_bundle.mjs').then(function (mp) {
            return mp.FilesetResolver.forVisionTasks(config.mediapipe.base + '/wasm').then(function (vision) {
                return mp.PoseLandmarker.createFromOptions(vision, {
                    baseOptions: { modelAssetPath: config.mediapipe.modelo, delegate: 'CPU' },
                    runningMode: 'VIDEO',
                    numPoses: 1
                }).then(function (landmarker) {
                    s.landmarker = landmarker;
                    s.modo = 'VIDEO';
                    s.conexoes = mp.PoseLandmarker.POSE_CONNECTIONS;
                });
            });
        }).catch(function (erro) {
            s.carregandoModelo = null;   // permite tentar de novo
            throw erro;
        });
        return s.carregandoModelo;
    }

    function garantirModo(modo) {
        if (s.modo === modo) return Promise.resolve();
        return s.landmarker.setOptions({ runningMode: modo }).then(function () { s.modo = modo; });
    }

    // ---------- geometria ----------
    function ponto(pts, i, largura, altura) {
        return { x: pts[i].x * largura, y: pts[i].y * altura, v: pts[i].visibility };
    }
    function distancia(a, b) { return Math.hypot(a.x - b.x, a.y - b.y); }
    function meio(a, b) { return { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 }; }
    function graus(rad) { return rad * 180 / Math.PI; }
    function visivel(p, minimo) { return p.visibility >= minimo && p.x >= 0 && p.x <= 1 && p.y >= 0 && p.y <= 1; }

    /**
     * Avalia se a pose serve para medir. Devolve { ok, nivel, texto, corpoInteiro }.
     * nivel: 'ok' | 'aviso' | 'erro'. largura/altura: tamanho da imagem em pixels.
     */
    function checarPose(pts, largura, altura) {
        if (!pts) {
            return { ok: false, nivel: 'erro', texto: 'Não encontrei você. Fique de frente para a câmera, com o corpo inteiro à vista.' };
        }
        var essenciais = [NARIZ, OMBRO_E, OMBRO_D, QUADRIL_E, QUADRIL_D];
        if (!essenciais.every(function (i) { return visivel(pts[i], 0.5); })) {
            return { ok: false, nivel: 'erro', texto: 'Enquadre a cabeça, os ombros e o quadril na imagem.' };
        }
        // Ombros muito estreitos em relação ao tronco = a pessoa está de lado.
        var ombros = distancia(ponto(pts, OMBRO_E, largura, altura), ponto(pts, OMBRO_D, largura, altura));
        var tronco = distancia(meio(ponto(pts, OMBRO_E, largura, altura), ponto(pts, OMBRO_D, largura, altura)),
            meio(ponto(pts, QUADRIL_E, largura, altura), ponto(pts, QUADRIL_D, largura, altura)));
        if (tronco === 0 || ombros / tronco < 0.35) {
            return { ok: false, nivel: 'erro', texto: 'Fique de frente para a câmera (não de lado).' };
        }
        var corpoInteiro = visivel(pts[TORNOZELO_E], 0.4) && visivel(pts[TORNOZELO_D], 0.4);
        if (!corpoInteiro) {
            return { ok: true, nivel: 'aviso', corpoInteiro: false, texto: 'Dá para avaliar, mas afaste-se um pouco para aparecer de corpo inteiro.' };
        }
        return { ok: true, nivel: 'ok', corpoInteiro: true, texto: 'Tudo certo. Pode tirar a foto.' };
    }

    /** Medidas de postura/proporção. Convenção: lados da pessoa; positivo = direita mais alta/deslocada. */
    function medir(pts, largura, altura, corpoInteiro) {
        var E = ponto(pts, OMBRO_E, largura, altura), D = ponto(pts, OMBRO_D, largura, altura);
        var QE = ponto(pts, QUADRIL_E, largura, altura), QD = ponto(pts, QUADRIL_D, largura, altura);
        var N = ponto(pts, NARIZ, largura, altura);
        var larguraOmbros = distancia(E, D), larguraQuadril = distancia(QE, QD);
        var mS = meio(E, D), mQ = meio(QE, QD);

        return {
            razao: larguraOmbros / larguraQuadril,
            ombros: graus(Math.atan2(E.y - D.y, Math.abs(E.x - D.x))),
            quadril: graus(Math.atan2(QE.y - QD.y, Math.abs(QE.x - QD.x))),
            cabeca: (mS.x - N.x) / larguraOmbros * 100,
            tronco: graus(Math.atan2(mQ.x - mS.x, mQ.y - mS.y)),
            corpo_inteiro: corpoInteiro ? '1' : '0'
        };
    }

    // ---------- desenho ----------
    var ctx = el.sobreposicao.getContext('2d');

    function limparSobreposicao() { ctx.clearRect(0, 0, el.sobreposicao.width, el.sobreposicao.height); }

    function desenharEsqueleto(pts, largura, altura) {
        if (el.sobreposicao.width !== largura) el.sobreposicao.width = largura;
        if (el.sobreposicao.height !== altura) el.sobreposicao.height = altura;
        ctx.clearRect(0, 0, largura, altura);
        if (!pts) return;

        var escala = Math.max(largura, altura) / 640;
        ctx.lineCap = 'round';
        ctx.lineWidth = 3 * escala;
        ctx.strokeStyle = 'rgba(124, 224, 195, .95)';
        s.conexoes.forEach(function (c) {
            var a = pts[c.start], b = pts[c.end];
            if (a.visibility < 0.4 || b.visibility < 0.4) return;
            ctx.beginPath();
            ctx.moveTo(a.x * largura, a.y * altura);
            ctx.lineTo(b.x * largura, b.y * altura);
            ctx.stroke();
        });
        pts.forEach(function (p, i) {
            if (p.visibility < 0.4) return;
            ctx.beginPath();
            ctx.arc(p.x * largura, p.y * altura, 4.5 * escala, 0, Math.PI * 2);
            ctx.fillStyle = [OMBRO_E, OMBRO_D, QUADRIL_E, QUADRIL_D].indexOf(i) !== -1 ? '#FFD666' : '#ffffff';
            ctx.fill();
        });
    }

    // ---------- câmera ----------
    function mensagemDeErroDaCamera(erro) {
        switch (erro && erro.name) {
            case 'NotAllowedError':
            case 'SecurityError':
                return 'Permita o acesso à câmera no navegador para continuar.';
            case 'NotFoundError':
            case 'OverconstrainedError':
                return 'Não encontrei nenhuma câmera neste dispositivo.';
            case 'NotReadableError':
                return 'A câmera está sendo usada por outro programa. Feche-o e tente de novo.';
            default:
                return 'Não foi possível abrir a câmera.';
        }
    }

    function falhaAoAbrir(texto) {
        pararCamera();
        irPara('ocioso');
        textoDoPalco(texto);
        toast(texto, true);
    }

    function pararCamera() {
        cancelAnimationFrame(s.raf);
        clearInterval(s.timerContagem);
        if (s.stream) {
            s.stream.getTracks().forEach(function (t) { t.stop(); });
            s.stream = null;
        }
        el.video.srcObject = null;
    }

    function abrirCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            textoDoPalco('A câmera só funciona em endereços seguros (https ou localhost) e em navegadores atuais.');
            toast('Este endereço não permite usar a câmera.', true);
            return;
        }

        descartarCaptura();
        irPara('carregando');
        el.mensagem.hidden = false;
        textoDoPalco('Preparando a câmera e o avaliador…');

        var camera = navigator.mediaDevices.getUserMedia({
            video: { facingMode: s.facing, width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false
        });
        // O modelo carrega enquanto o navegador pede a permissão da câmera.
        camera.catch(function () { /* tratado abaixo */ });

        Promise.all([camera, carregarModelo().then(function () { return garantirModo('VIDEO'); }).then(function () { return 'ok'; }, function (e) { return e || new Error('modelo'); })])
            .then(function (r) {
                s.stream = r[0];
                if (r[1] !== 'ok') {
                    falhaAoAbrir('Não foi possível carregar o avaliador. Recarregue a página e tente de novo.');
                    return;
                }
                el.video.srcObject = s.stream;
                return el.video.play().then(function () {
                    el.palco.style.setProperty('--proporcao', (el.video.videoWidth / el.video.videoHeight).toFixed(4));
                    irPara('camera');
                    orientar('aviso', 'Procurando você…');
                    s.pausado = false;
                    s.ultimoQuadro = 0;
                    laco();
                    navigator.mediaDevices.enumerateDevices().then(function (lista) {
                        s.temVariasCameras = lista.filter(function (d) { return d.kind === 'videoinput'; }).length > 1;
                        if (s.fase === 'camera') mostrar(el.virar, s.temVariasCameras);
                    });
                });
            })
            .catch(function (erro) { falhaAoAbrir(mensagemDeErroDaCamera(erro)); });
    }

    // Detecção ao vivo (cerca de 10 quadros por segundo) para orientar o enquadramento.
    function laco() {
        s.raf = requestAnimationFrame(function (agora) {
            if (s.fase !== 'camera' && s.fase !== 'contagem') return;
            if (!s.pausado && agora - s.ultimoQuadro > 100 && el.video.readyState >= 2) {
                s.ultimoQuadro = agora;
                var resultado = s.landmarker.detectForVideo(el.video, performance.now());
                var pts = resultado.landmarks[0] || null;
                desenharEsqueleto(pts, el.video.videoWidth, el.video.videoHeight);
                s.ultimaOrientacao = checarPose(pts, el.video.videoWidth, el.video.videoHeight);
                orientar(s.ultimaOrientacao.nivel, s.ultimaOrientacao.texto);
            }
            laco();
        });
    }

    el.abrir.addEventListener('click', abrirCamera);

    el.fechar.addEventListener('click', function () {
        pararCamera();
        irPara('ocioso');
        textoDoPalco('Sua câmera está desligada.');
    });

    el.virar.addEventListener('click', function () {
        s.facing = s.facing === 'user' ? 'environment' : 'user';
        pararCamera();
        abrirCamera();
    });

    // ---------- captura ----------
    function mostrarNumero(n) {
        el.contagem.textContent = String(n);
        el.contagem.hidden = false;
    }

    el.iniciarContagem.addEventListener('click', function () {
        var n = config.contagem;
        irPara('contagem');
        mostrarNumero(n);
        s.timerContagem = setInterval(function () {
            n -= 1;
            if (n <= 0) {
                clearInterval(s.timerContagem);
                capturar();
            } else {
                mostrarNumero(n);
            }
        }, 1000);
    });

    el.cancelar.addEventListener('click', function () {
        clearInterval(s.timerContagem);
        irPara('camera');
    });

    el.agora.addEventListener('click', capturar);

    function descartarCaptura() {
        if (s.captura && s.captura.url) URL.revokeObjectURL(s.captura.url);
        s.captura = null;
        el.resultado.hidden = true;
        el.resultadoCorpo.textContent = '';
        el.foto.removeAttribute('src');
        limparSobreposicao();
    }

    function capturar() {
        if (s.fase !== 'camera' && s.fase !== 'contagem') return;
        s.pausado = true;
        clearInterval(s.timerContagem);
        el.contagem.hidden = true;

        // Congela o quadro atual em tamanho original.
        var L = el.video.videoWidth, A = el.video.videoHeight;
        var quadro = document.createElement('canvas');
        quadro.width = L; quadro.height = A;
        quadro.getContext('2d').drawImage(el.video, 0, 0, L, A);

        garantirModo('IMAGE').then(function () {
            var resultado = s.landmarker.detect(quadro);
            var pts = resultado.landmarks[0] || null;
            var pose = checarPose(pts, L, A);

            if (!pose.ok) {
                // Continua com a câmera ligada para tentar de novo.
                return garantirModo('VIDEO').then(function () {
                    s.pausado = false;
                    irPara('camera');
                    toast(pose.texto, true);
                });
            }

            var medidas = medir(pts, L, A, pose.corpoInteiro);
            return prepararFoto(quadro).then(function (foto) {
                pararCamera();
                s.captura = { blob: foto.blob, url: foto.url, medidas: medidas, pontos: pts, largura: foto.largura, altura: foto.altura, corpoInteiro: pose.corpoInteiro };
                irPara('analisando');
                el.foto.src = foto.url;
                el.palco.style.setProperty('--proporcao', (foto.largura / foto.altura).toFixed(4));
                desenharEsqueleto(pts, foto.largura, foto.altura);
                return interpretar();
            });
        }).catch(function () {
            s.pausado = false;
            irPara('camera');
            toast('Não foi possível analisar a foto. Tente de novo.', true);
        });
    }

    /** Reduz a foto (até 1000 px no maior lado) e gera o JPEG que será guardado. */
    function prepararFoto(quadro) {
        var maior = Math.max(quadro.width, quadro.height);
        var escala = Math.min(1, 1000 / maior);
        var saida = document.createElement('canvas');
        saida.width = Math.round(quadro.width * escala);
        saida.height = Math.round(quadro.height * escala);
        saida.getContext('2d').drawImage(quadro, 0, 0, saida.width, saida.height);

        return new Promise(function (resolve, reject) {
            saida.toBlob(function (blob) {
                if (!blob) { reject(new Error('jpeg')); return; }
                resolve({ blob: blob, url: URL.createObjectURL(blob), largura: saida.width, altura: saida.height });
            }, 'image/jpeg', 0.88);
        });
    }

    // ---------- resultado ----------
    function dadosDasMedidas(m) {
        return {
            razao: m.razao.toFixed(3), ombros: m.ombros.toFixed(2), quadril: m.quadril.toFixed(2),
            cabeca: m.cabeca.toFixed(2), tronco: m.tronco.toFixed(2), corpo_inteiro: m.corpo_inteiro
        };
    }

    function interpretar() {
        return api('interpretar', dadosDasMedidas(s.captura.medidas)).then(function (r) {
            if (!r.json.ok) {
                irPara('resultado');
                mostrarResultadoComErro(r.json.mensagem);
                return;
            }
            irPara('resultado');
            mostrarResultado(r.json.interpretacao);
        }).catch(function () {
            irPara('resultado');
            mostrarResultadoComErro('Não foi possível conectar. Verifique sua conexão e tente de novo.');
        });
    }

    function medidasEmLista(interpretacao) {
        var lista = elemento('ul', 'medidas');
        interpretacao.itens.forEach(function (item) {
            var li = elemento('li', 'medida');
            var cabecalho = elemento('div', 'medida__topo');
            cabecalho.append(elemento('strong', 'medida__titulo', item.titulo), elemento('span', 'medida__valor', item.valor),
                elemento('span', 'selo selo--' + item.estado, item.rotulo));
            li.append(cabecalho, elemento('p', 'medida__texto', item.texto));
            lista.append(li);
        });
        return lista;
    }

    function mostrarResultado(interpretacao) {
        el.resultadoCorpo.textContent = '';
        el.resultadoCorpo.append(
            medidasEmLista(interpretacao),
            elemento('p', 'resultado-avaliacao__resumo', interpretacao.resumo),
            elemento('p', 'nota', 'Os lados (direito e esquerdo) são os da pessoa avaliada: o lado direito dela aparece à esquerda da foto.'),
            elemento('p', 'nota', interpretacao.aviso)
        );

        var acoes = elemento('div', 'avaliador__acoes');
        var salvar = elemento('button', 'btn btn--primary', 'Salvar no meu perfil');
        salvar.type = 'button';
        salvar.addEventListener('click', function () { salvarAvaliacao(salvar); });
        var outra = elemento('button', 'btn btn--ghost', 'Descartar e tirar outra');
        outra.type = 'button';
        outra.addEventListener('click', abrirCamera);
        acoes.append(salvar, outra);
        el.resultadoCorpo.append(acoes);

        el.resultado.hidden = false;
        el.resultado.scrollIntoView({ behavior: 'smooth', block: 'start' });
        el.resultado.focus({ preventScroll: true });
    }

    function mostrarResultadoComErro(mensagem) {
        el.resultadoCorpo.textContent = '';
        var aviso = elemento('div', 'alert alert--erro', mensagem);
        aviso.setAttribute('role', 'alert');
        var outra = elemento('button', 'btn btn--primary', 'Tirar outra foto');
        outra.type = 'button';
        outra.addEventListener('click', abrirCamera);
        el.resultadoCorpo.append(aviso, outra);
        el.resultado.hidden = false;
    }

    function salvarAvaliacao(botao) {
        if (!s.captura) return;
        botao.disabled = true;
        botao.textContent = 'Salvando…';

        api('salvar', dadosDasMedidas(s.captura.medidas), s.captura.blob).then(function (r) {
            if (r.json.ok) {
                descartarCaptura();
                irPara('ocioso');
                textoDoPalco('Avaliação salva! Quando quiser, faça uma nova.');
                renderHistorico(r.json.avaliacoes);
                toast(r.json.mensagem);
                porId('historico').scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                toast(r.json.mensagem, true);
                botao.disabled = false;
                botao.textContent = 'Salvar no meu perfil';
            }
        }).catch(function () {
            toast('Não foi possível salvar. Verifique sua conexão e tente de novo.', true);
            botao.disabled = false;
            botao.textContent = 'Salvar no meu perfil';
        });
    }

    // ---------- histórico ----------
    function tabelaDeComparacao(avaliacao, anterior) {
        var caixa = elemento('div', 'comparacao');
        caixa.append(elemento('h3', '', 'Última avaliação comparada com a anterior'),
            elemento('p', 'nota', dataHora(avaliacao.data) + ' comparada com ' + dataHora(anterior.data) + '. Nos alinhamentos, valores menores significam mais alinhado.'));

        var rolagem = elemento('div', 'tabela-rolagem');
        var tabela = elemento('table', 'tabela');
        var cab = tabela.createTHead().insertRow();
        ['Medida', 'Anterior', 'Atual', 'Variação', 'Leitura'].forEach(function (t) {
            var th = elemento('th', '', t); th.scope = 'col'; cab.append(th);
        });
        var corpo = tabela.createTBody();
        avaliacao.comparacao.forEach(function (l) {
            var tr = corpo.insertRow();
            [l.titulo, l.anterior, l.atual, l.variacao, l.leitura || '—'].forEach(function (t) { tr.insertCell().textContent = t; });
        });
        rolagem.append(tabela);
        caixa.append(rolagem);
        return caixa;
    }

    function cartaoDaAvaliacao(a) {
        var cartao = elemento('article', 'avaliacao');
        var imagem = elemento('img', 'avaliacao__foto');
        imagem.src = a.foto;
        imagem.loading = 'lazy';
        imagem.alt = 'Foto da avaliação de ' + dataHora(a.data);

        var corpo = elemento('div', 'avaliacao__corpo');
        corpo.append(elemento('h3', 'avaliacao__data', dataHora(a.data)));

        var atencao = a.interpretacao.itens.filter(function (i) { return i.estado === 'leve' || i.estado === 'acentuado'; });
        var selos = elemento('div', 'avaliacao__selos');
        if (!atencao.length) {
            selos.append(elemento('span', 'selo selo--ok', 'Tudo alinhado'));
        } else {
            atencao.forEach(function (i) { selos.append(elemento('span', 'selo selo--' + i.estado, i.curto + ': ' + i.rotulo.toLowerCase())); });
        }
        corpo.append(selos);

        var detalhes = elemento('details', 'avaliacao__detalhes');
        detalhes.append(elemento('summary', '', 'Ver detalhes'), medidasEmLista(a.interpretacao), elemento('p', 'nota', a.interpretacao.resumo));
        corpo.append(detalhes);

        var excluir = elemento('button', 'btn btn--ghost btn--sm btn--perigo', 'Excluir');
        excluir.type = 'button';
        excluir.setAttribute('aria-label', 'Excluir a avaliação de ' + dataHora(a.data));
        excluir.addEventListener('click', function () { excluirAvaliacao(a); });
        corpo.append(excluir);

        cartao.append(imagem, corpo);
        return cartao;
    }

    function renderHistorico(lista) {
        el.historico.textContent = '';
        if (!lista.length) {
            el.historico.append(elemento('p', 'hist__vazio', 'Você ainda não salvou nenhuma avaliação. Abra a câmera acima para fazer a primeira.'));
            return;
        }
        if (lista[0].comparacao) el.historico.append(tabelaDeComparacao(lista[0], lista[1]));

        var grade = elemento('div', 'avaliacoes');
        lista.forEach(function (a) { grade.append(cartaoDaAvaliacao(a)); });
        el.historico.append(grade);
    }

    function excluirAvaliacao(a) {
        if (!window.confirm('Excluir a avaliação de ' + dataHora(a.data) + '? A foto também será apagada.')) return;
        api('excluir', { id: a.id }).then(function (r) {
            if (r.json.ok) {
                renderHistorico(r.json.avaliacoes);
                toast(r.json.mensagem);
            } else {
                toast(r.json.mensagem, true);
            }
        }).catch(function () { toast('Não foi possível excluir agora.', true); });
    }

    // ---------- início ----------
    irPara('ocioso');
    api('listar').then(function (r) {
        if (r.json.ok) renderHistorico(r.json.avaliacoes);
        else el.historico.textContent = r.json.mensagem;
    }).catch(function () {
        el.historico.textContent = '';
        el.historico.append(elemento('p', 'hist__vazio', 'Não foi possível carregar suas avaliações agora.'));
    });

    // Solta a câmera ao sair da página.
    window.addEventListener('pagehide', pararCamera);
})();
