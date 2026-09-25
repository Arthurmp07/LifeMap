// Página de IMC: cálculo ao vivo, salvamento e histórico (lista + gráfico).
// A configuração vem do PHP em <script id="imc-config" type="application/json">.
(function () {
    var config = JSON.parse(document.getElementById('imc-config').textContent);
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var fmt = window.GraficoIMC;

    var CATEGORIAS = config.categorias.map(function (c) {
        return { chave: c.chave, nome: c.nome, limite: c.limite === null ? Infinity : c.limite };
    });
    function categoriaDe(imc) {
        return CATEGORIAS.find(function (c) { return imc < c.limite; });
    }

    // ---------- elementos ----------
    var campoPeso = document.getElementById('peso');
    var campoAltura = document.getElementById('altura');
    var aviso = document.getElementById('imc-aviso');
    var saida = document.getElementById('resultado');
    var etiqueta = document.getElementById('categoria');
    var escala = document.getElementById('escala');
    var marcador = document.getElementById('marcador');
    var botao = document.getElementById('salvar-imc');
    var toastEl = document.getElementById('toast');
    var historicoEl = document.getElementById('hist-conteudo');
    var timerToast;

    function elemento(nome, classe, texto) {
        var no = document.createElement(nome);
        if (classe) no.className = classe;
        if (texto !== undefined) no.textContent = texto;
        return no;
    }

    // ---------- cálculo ----------
    // Aceita "1,75" e "1.75". Alturas acima de 3 são tratadas como centímetros (175 -> 1,75).
    function numero(texto) {
        var n = parseFloat(String(texto).replace(',', '.'));
        return isNaN(n) ? null : n;
    }
    function lerAltura() {
        var n = numero(campoAltura.value);
        return n !== null && n > 3 ? n / 100 : n;
    }

    function limpar() {
        saida.textContent = '—';
        etiqueta.textContent = 'Preencha peso e altura';
        etiqueta.className = 'imc__categoria';
        escala.classList.remove('is-ativa');
        document.querySelectorAll('.tabela--cat tr.is-current').forEach(function (tr) { tr.classList.remove('is-current'); });
        botao.disabled = true;
    }

    function calcular() {
        var peso = numero(campoPeso.value);
        var altura = lerAltura();
        var lim = config.limites;
        var preenchido = campoPeso.value.trim() !== '' && campoAltura.value.trim() !== '';
        var valido = peso !== null && altura !== null &&
            peso >= lim.pesoMin && peso <= lim.pesoMax && altura >= lim.alturaMin && altura <= lim.alturaMax;

        aviso.hidden = !preenchido || valido;
        if (!valido) { limpar(); return null; }

        var imc = peso / (altura * altura);
        var cat = categoriaDe(imc);

        saida.textContent = fmt.numero(imc, 1);
        etiqueta.textContent = cat.nome;
        etiqueta.className = 'imc__categoria cat-' + cat.chave;

        marcador.style.left = Math.min(100, Math.max(0, (imc - 15) / 30 * 100)) + '%';
        escala.classList.add('is-ativa');

        document.querySelectorAll('.tabela--cat tbody tr').forEach(function (tr) {
            tr.classList.toggle('is-current', tr.dataset.cat === cat.chave);
        });

        botao.disabled = false;
        return { peso: peso, altura: altura };
    }

    // ---------- avisos ----------
    function mostrarToast(mensagem, opcoes) {
        opcoes = opcoes || {};
        toastEl.textContent = mensagem;
        if (opcoes.link) {
            var a = document.createElement('a');
            a.href = opcoes.link.href;
            a.textContent = opcoes.link.texto;
            toastEl.append(' ', a);
        }
        toastEl.classList.toggle('toast--erro', !!opcoes.erro);
        toastEl.classList.add('is-visible');
        clearTimeout(timerToast);
        timerToast = setTimeout(function () { toastEl.classList.remove('is-visible'); }, 5000);
    }

    // ---------- API ----------
    function api(acao, dados) {
        var opcoes = { headers: { 'X-CSRF-Token': csrf } };
        var url = config.api + '?acao=' + acao;

        if (dados) {
            var corpo = new FormData();
            corpo.append('acao', acao);
            Object.keys(dados).forEach(function (k) { corpo.append(k, dados[k]); });
            opcoes.method = 'POST';
            opcoes.body = corpo;
            url = config.api;
        }

        return fetch(url, opcoes).then(function (resposta) {
            return resposta.json().then(function (json) { return { status: resposta.status, json: json }; });
        });
    }

    function falhaDeRede() {
        mostrarToast('Não foi possível conectar. Verifique sua conexão e tente de novo.', { erro: true });
    }

    // ---------- histórico ----------
    var registros = [];
    var mostrarTodos = false;
    var LINHAS_INICIAIS = 8;

    function renderHistorico() {
        if (!historicoEl) return;
        historicoEl.textContent = '';

        if (!registros.length) {
            historicoEl.append(elemento('p', 'hist__vazio', 'Você ainda não salvou nenhum cálculo. Preencha peso e altura acima e clique em “Salvar IMC”.'));
            return;
        }

        var ultimo = registros[registros.length - 1];
        var anterior = registros[registros.length - 2];
        var cat = categoriaDe(ultimo.imc);

        // Resumo (stat tile)
        var stat = elemento('div', 'stat');
        stat.append(
            elemento('span', 'stat__label', 'IMC mais recente'),
            elemento('span', 'stat__value', fmt.numero(ultimo.imc, 1)),
            elemento('span', 'imc__categoria cat-' + cat.chave, cat.nome)
        );
        if (anterior) {
            var delta = ultimo.imc - anterior.imc;
            var texto = Math.abs(delta) < 0.05
                ? 'Sem variação desde ' + fmt.dataCurta(new Date(anterior.data))
                : (delta > 0 ? '+' : '−') + fmt.numero(Math.abs(delta), 1) + ' desde ' + fmt.dataCurta(new Date(anterior.data));
            stat.append(elemento('span', 'stat__delta', texto));
        }

        var grafico = elemento('div', 'grafico');
        var desenhou = window.GraficoIMC.desenhar(grafico, registros, categoriaDe);
        if (!desenhou) {
            grafico.append(elemento('p', 'hist__vazio', 'Salve mais um cálculo para ver o gráfico da sua evolução.'));
        }

        var topo = elemento('div', 'hist');
        topo.append(stat, grafico);

        historicoEl.append(topo, elemento('h3', 'hist__titulo', 'Registros'), montarTabela());
    }

    function montarTabela() {
        var novoPrimeiro = registros.slice().reverse();
        var visiveis = mostrarTodos ? novoPrimeiro : novoPrimeiro.slice(0, LINHAS_INICIAIS);

        var rolagem = elemento('div', 'tabela-rolagem');
        var tabela = elemento('table', 'tabela tabela--registros');

        var cabecalho = tabela.createTHead().insertRow();
        ['Data', 'Peso', 'Altura', 'IMC', 'Categoria'].forEach(function (titulo) {
            var th = elemento('th', '', titulo);
            th.scope = 'col';
            cabecalho.append(th);
        });
        var thAcao = elemento('th', '');
        thAcao.scope = 'col';
        thAcao.append(elemento('span', 'visually-hidden', 'Ações'));
        cabecalho.append(thAcao);

        var corpo = tabela.createTBody();
        visiveis.forEach(function (r) {
            var c = categoriaDe(r.imc);
            var data = new Date(r.data);
            var linha = corpo.insertRow();

            linha.insertCell().textContent = fmt.dataCompleta(data);
            linha.insertCell().textContent = fmt.numero(r.peso, r.peso % 1 ? 1 : 0) + ' kg';
            linha.insertCell().textContent = fmt.numero(r.altura, 2) + ' m';
            linha.insertCell().textContent = fmt.numero(r.imc, 1);
            linha.insertCell().append(elemento('span', 'imc__categoria imc__categoria--sm cat-' + c.chave, c.nome));

            var botaoExcluir = elemento('button', 'btn btn--ghost btn--sm btn--perigo', 'Excluir');
            botaoExcluir.type = 'button';
            botaoExcluir.setAttribute('aria-label', 'Excluir o registro de ' + fmt.dataCompleta(data));
            botaoExcluir.addEventListener('click', function () { excluir(r, data); });
            linha.insertCell().append(botaoExcluir);
        });

        rolagem.append(tabela);

        if (novoPrimeiro.length > LINHAS_INICIAIS) {
            var alternar = elemento('button', 'btn btn--ghost btn--sm', mostrarTodos ? 'Mostrar menos' : 'Mostrar todos (' + novoPrimeiro.length + ')');
            alternar.type = 'button';
            alternar.addEventListener('click', function () { mostrarTodos = !mostrarTodos; renderHistorico(); });
            var envoltorio = elemento('div', 'tabela-acoes');
            envoltorio.append(alternar);
            rolagem.append(envoltorio);
        }
        return rolagem;
    }

    function excluir(registro, data) {
        if (!window.confirm('Excluir o registro de ' + fmt.dataCompleta(data) + '?')) return;

        api('excluir', { id: registro.id }).then(function (r) {
            if (r.json.ok) {
                registros = r.json.registros;
                renderHistorico();
                mostrarToast(r.json.mensagem);
            } else {
                mostrarToast(r.json.mensagem, { erro: true });
            }
        }).catch(falhaDeRede);
    }

    // ---------- eventos ----------
    campoPeso.addEventListener('input', calcular);
    campoAltura.addEventListener('input', calcular);

    botao.addEventListener('click', function () {
        var dados = calcular();
        if (!dados) return;

        botao.disabled = true;
        api('salvar', { peso: dados.peso, altura: dados.altura })
            .then(function (r) {
                if (r.json.ok) {
                    registros = r.json.registros;
                    renderHistorico();
                    mostrarToast(r.json.mensagem);
                } else if (r.status === 401) {
                    mostrarToast(r.json.mensagem, { erro: true, link: { texto: 'Entrar', href: config.login } });
                } else {
                    mostrarToast(r.json.mensagem, { erro: true });
                }
            })
            .catch(falhaDeRede)
            .finally(function () { botao.disabled = false; });
    });

    // ---------- início ----------
    if (config.alturaPadrao) {
        campoAltura.value = fmt.numero(config.alturaPadrao, 2);
    }
    calcular();

    if (config.logado && historicoEl) {
        api('listar').then(function (r) {
            if (r.json.ok) {
                registros = r.json.registros;
                renderHistorico();
            } else {
                historicoEl.textContent = '';
                historicoEl.append(elemento('p', 'hist__vazio', r.json.mensagem));
            }
        }).catch(function () {
            historicoEl.textContent = '';
            historicoEl.append(elemento('p', 'hist__vazio', 'Não foi possível carregar seu histórico agora.'));
        });
    }
})();
