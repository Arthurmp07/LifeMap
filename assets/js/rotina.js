// Página de Rotina: calendário mensal, agenda do dia, resumo de tempo por
// categoria e registro de humor. A configuração vem do PHP em
// <script id="rotina-config" type="application/json">.
(function () {
    'use strict';

    var config = JSON.parse(document.getElementById('rotina-config').textContent);
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    var MESES = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    var DIAS = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
    var ALTURA_HORA = 48; // px por hora na agenda

    var CATEGORIAS = config.categorias;
    var categoriaPor = {};
    CATEGORIAS.forEach(function (c) { categoriaPor[c.chave] = c; });
    var humorPor = {};
    config.humores.forEach(function (h) { humorPor[h.nivel] = h; });

    // ---------- utilidades ----------
    function dois(n) { return (n < 10 ? '0' : '') + n; }
    function iso(d) { return d.getFullYear() + '-' + dois(d.getMonth() + 1) + '-' + dois(d.getDate()); }
    function lerIso(texto) { var p = texto.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
    function somarDias(d, n) { return new Date(d.getFullYear(), d.getMonth(), d.getDate() + n); }
    function hhmm(d) { return dois(d.getHours()) + ':' + dois(d.getMinutes()); }
    function maiuscula(t) { return t.charAt(0).toUpperCase() + t.slice(1); }
    function chaveMes(ano, mes) { return ano + '-' + dois(mes + 1); }

    function duracao(minutos) {
        minutos = Math.round(minutos);
        var h = Math.floor(minutos / 60), m = minutos % 60;
        if (h && m) return h + ' h ' + dois(m);
        if (h) return h + ' h';
        return m + ' min';
    }

    function elemento(nome, classe, texto) {
        var no = document.createElement(nome);
        if (classe) no.className = classe;
        if (texto !== undefined) no.textContent = texto;
        return no;
    }

    function porId(id) { return document.getElementById(id); }

    // ---------- estado ----------
    var hoje = lerIso(config.hoje);
    var estado = {
        ano: hoje.getFullYear(),
        mes: hoje.getMonth(),
        selecionado: config.hoje,
        dados: {}          // "AAAA-MM" -> { eventos, humor }
    };

    function dadosAtuais() {
        return estado.dados[chaveMes(estado.ano, estado.mes)] || { eventos: [], humor: {} };
    }

    function eventosDoDia(dia) {
        var inicio = dia.getTime(), fim = somarDias(dia, 1).getTime();
        return dadosAtuais().eventos.filter(function (ev) { return ev.ini.getTime() < fim && ev.fim.getTime() > inicio; });
    }

    // ---------- API ----------
    function api(acao, dados) {
        var opcoes = { headers: { 'X-CSRF-Token': csrf } };
        var url = config.api;

        if (dados && dados.__get) {
            delete dados.__get;
            url += '?acao=' + acao + '&' + Object.keys(dados).map(function (k) { return k + '=' + encodeURIComponent(dados[k]); }).join('&');
        } else {
            var corpo = new FormData();
            corpo.append('acao', acao);
            Object.keys(dados || {}).forEach(function (k) { corpo.append(k, dados[k]); });
            opcoes.method = 'POST';
            opcoes.body = corpo;
        }

        return fetch(url, opcoes).then(function (resposta) {
            return resposta.json().then(function (json) { return { status: resposta.status, json: json }; });
        });
    }

    // ---------- avisos ----------
    var toastEl = porId('toast');
    var timerToast;
    function mostrarToast(mensagem, erro) {
        toastEl.textContent = mensagem;
        toastEl.classList.toggle('toast--erro', !!erro);
        toastEl.classList.add('is-visible');
        clearTimeout(timerToast);
        timerToast = setTimeout(function () { toastEl.classList.remove('is-visible'); }, 4500);
    }
    function falhaDeRede() {
        mostrarToast('Não foi possível conectar. Verifique sua conexão e tente de novo.', true);
    }

    // ---------- carregamento ----------
    var calendarioEl = document.querySelector('.cal');
    var tokenCarga = 0;

    function carregarMes(ano, mes) {
        var chave = chaveMes(ano, mes);
        if (estado.dados[chave]) return Promise.resolve();

        var meu = ++tokenCarga;
        calendarioEl.classList.add('is-carregando');
        return api('mes', { __get: true, ano: ano, mes: mes + 1 }).then(function (r) {
            if (!r.json.ok) { mostrarToast(r.json.mensagem, true); return; }
            r.json.eventos.forEach(function (ev) { ev.ini = new Date(ev.inicio); ev.fim = new Date(ev.fim); });
            estado.dados[chave] = { eventos: r.json.eventos, humor: r.json.humor };
        }).catch(falhaDeRede).finally(function () {
            if (meu === tokenCarga) calendarioEl.classList.remove('is-carregando');
        });
    }

    function mostrarTudo() {
        renderCalendario();
        renderDia();
        renderHumor();
    }

    /** Seleciona um dia (trocando de mês se preciso) e mostra tudo. */
    function selecionar(data, focar) {
        estado.selecionado = iso(data);
        estado.ano = data.getFullYear();
        estado.mes = data.getMonth();
        mostrarTudo();
        if (focar) focarDia();

        var mesDaCarga = chaveMes(estado.ano, estado.mes);
        carregarMes(estado.ano, estado.mes).then(function () {
            // Só redesenha se a pessoa não tiver navegado para outro mês nesse meio tempo.
            if (chaveMes(estado.ano, estado.mes) === mesDaCarga) {
                mostrarTudo();
                if (focar) focarDia();
            }
        });
    }

    function recarregar() {
        estado.dados = {};
        selecionar(lerIso(estado.selecionado));
    }

    // ---------- calendário ----------
    var corpoCal = porId('cal-corpo');

    function renderCalendario() {
        var dados = dadosAtuais();
        porId('cal-titulo').textContent = maiuscula(MESES[estado.mes]) + ' de ' + estado.ano;

        var primeiro = new Date(estado.ano, estado.mes, 1);
        var inicioGrade = somarDias(primeiro, -primeiro.getDay());

        corpoCal.textContent = '';
        for (var semana = 0; semana < 6; semana++) {
            var linha = corpoCal.insertRow();
            for (var i = 0; i < 7; i++) {
                linha.insertCell().append(botaoDoDia(somarDias(inicioGrade, semana * 7 + i), dados));
            }
        }
    }

    function botaoDoDia(dia, dados) {
        var chave = iso(dia);
        var eventos = eventosDoDia(dia);
        var humor = dados.humor[chave];

        var botao = elemento('button', 'cal__dia');
        botao.type = 'button';
        botao.dataset.data = chave;
        if (dia.getMonth() !== estado.mes) botao.classList.add('is-fora');
        if (chave === config.hoje) { botao.classList.add('is-hoje'); botao.setAttribute('aria-current', 'date'); }
        if (chave === estado.selecionado) botao.classList.add('is-selecionado');
        botao.setAttribute('aria-pressed', chave === estado.selecionado ? 'true' : 'false');
        botao.tabIndex = chave === estado.selecionado ? 0 : -1;

        botao.append(elemento('span', 'cal__numero', String(dia.getDate())));

        // Uma bolinha por categoria presente no dia (na ordem das categorias).
        var presentes = CATEGORIAS.filter(function (c) {
            return eventos.some(function (ev) { return ev.categoria === c.chave; });
        });
        if (presentes.length) {
            var pontos = elemento('span', 'cal__pontos');
            presentes.slice(0, 4).forEach(function (c) { pontos.append(elemento('span', 'ponto ev-' + c.chave)); });
            if (presentes.length > 4) pontos.append(elemento('span', 'cal__mais', '+'));
            botao.append(pontos);
        }
        if (humor) botao.append(elemento('span', 'cal__humor', humorPor[humor.nivel].emoji));

        var rotulo = dia.getDate() + ' de ' + MESES[dia.getMonth()] + ' de ' + dia.getFullYear();
        if (chave === config.hoje) rotulo += ', hoje';
        if (eventos.length) rotulo += ', ' + eventos.length + (eventos.length === 1 ? ' evento' : ' eventos');
        if (humor) rotulo += ', humor: ' + humorPor[humor.nivel].nome;
        botao.setAttribute('aria-label', rotulo);
        return botao;
    }

    function focarDia() {
        var botao = corpoCal.querySelector('[data-data="' + estado.selecionado + '"]');
        if (botao) botao.focus();
    }

    corpoCal.addEventListener('click', function (ev) {
        var botao = ev.target.closest('.cal__dia');
        if (botao) selecionar(lerIso(botao.dataset.data), true);
    });

    // Setas, Home/End e PageUp/PageDown movem a seleção (só o dia selecionado é tabulável).
    corpoCal.addEventListener('keydown', function (ev) {
        var botao = ev.target.closest('.cal__dia');
        if (!botao) return;

        var d = lerIso(botao.dataset.data);
        var novo = null;
        switch (ev.key) {
            case 'ArrowLeft': novo = somarDias(d, -1); break;
            case 'ArrowRight': novo = somarDias(d, 1); break;
            case 'ArrowUp': novo = somarDias(d, -7); break;
            case 'ArrowDown': novo = somarDias(d, 7); break;
            case 'Home': novo = somarDias(d, -d.getDay()); break;
            case 'End': novo = somarDias(d, 6 - d.getDay()); break;
            case 'PageUp': novo = mesmoDiaEm(d, -1); break;
            case 'PageDown': novo = mesmoDiaEm(d, 1); break;
        }
        if (novo) { ev.preventDefault(); selecionar(novo, true); }
    });

    /** O mesmo dia do mês, N meses depois (limitado ao último dia do mês de destino). */
    function mesmoDiaEm(d, meses) {
        var destino = new Date(d.getFullYear(), d.getMonth() + meses, 1);
        var ultimo = new Date(destino.getFullYear(), destino.getMonth() + 1, 0).getDate();
        return new Date(destino.getFullYear(), destino.getMonth(), Math.min(d.getDate(), ultimo));
    }

    porId('cal-anterior').addEventListener('click', function () { selecionar(mesmoDiaEm(lerIso(estado.selecionado), -1)); });
    porId('cal-proximo').addEventListener('click', function () { selecionar(mesmoDiaEm(lerIso(estado.selecionado), 1)); });
    porId('cal-hoje').addEventListener('click', function () { selecionar(hoje, true); });

    // ---------- dia: resumo e agenda ----------
    var resumoEl = porId('dia-resumo');
    var agendaEl = porId('dia-agenda');

    function renderDia() {
        var dia = lerIso(estado.selecionado);
        var eventos = eventosDoDia(dia);

        var titulo = maiuscula(DIAS[dia.getDay()]) + ', ' + dia.getDate() + ' de ' + MESES[dia.getMonth()];
        if (dia.getFullYear() !== hoje.getFullYear()) titulo += ' de ' + dia.getFullYear();
        porId('dia-titulo').textContent = titulo;
        porId('dia-sub').textContent = eventos.length
            ? eventos.length + (eventos.length === 1 ? ' compromisso' : ' compromissos')
            : 'Nada planejado';

        renderResumo(dia, eventos);
        renderAgenda(dia, eventos);
    }

    /** Minutos cobertos por uma lista de intervalos [ini, fim] (sobreposições contam uma vez). */
    function uniao(intervalos) {
        var lista = intervalos.slice().sort(function (a, b) { return a[0] - b[0]; });
        var total = 0, ini = null, fim = null;
        lista.forEach(function (s) {
            if (fim === null || s[0] > fim) {
                if (fim !== null) total += fim - ini;
                ini = s[0]; fim = s[1];
            } else {
                fim = Math.max(fim, s[1]);
            }
        });
        return fim === null ? total : total + (fim - ini);
    }

    /** Intervalo do evento dentro do dia, em minutos desde 00:00. */
    function recorteNoDia(ev, dia) {
        var zero = dia.getTime();
        var ini = Math.max(ev.ini.getTime(), zero);
        var fim = Math.min(ev.fim.getTime(), zero + 86400000);
        return [(ini - zero) / 60000, (fim - zero) / 60000];
    }

    function renderResumo(dia, eventos) {
        resumoEl.textContent = '';
        if (!eventos.length) return;

        var porCategoria = {}, todos = [];
        eventos.forEach(function (ev) {
            var recorte = recorteNoDia(ev, dia);
            (porCategoria[ev.categoria] = porCategoria[ev.categoria] || []).push(recorte);
            todos.push(recorte);
        });

        var minutos = {};
        CATEGORIAS.forEach(function (c) { if (porCategoria[c.chave]) minutos[c.chave] = uniao(porCategoria[c.chave]); });
        var planejado = uniao(todos);
        var livre = Math.max(0, 1440 - planejado);

        var barra = elemento('div', 'resumo__barra');
        barra.setAttribute('role', 'img');
        var partes = [];
        CATEGORIAS.forEach(function (c) {
            if (!minutos[c.chave]) return;
            var seg = elemento('span', 'resumo__seg ev-' + c.chave);
            seg.style.flexGrow = minutos[c.chave];
            seg.title = c.nome + ': ' + duracao(minutos[c.chave]);
            barra.append(seg);
            partes.push(c.nome + ' ' + duracao(minutos[c.chave]));
        });
        var segLivre = elemento('span', 'resumo__seg resumo__seg--livre');
        segLivre.style.flexGrow = livre;
        segLivre.title = 'Livre: ' + duracao(livre);
        barra.append(segLivre);
        barra.setAttribute('aria-label', 'Distribuição das 24 horas do dia: ' + partes.join(', ') + '; livre ' + duracao(livre));

        var lista = elemento('ul', 'resumo__lista');
        CATEGORIAS.forEach(function (c) {
            if (!minutos[c.chave]) return;
            var item = elemento('li', 'ev-' + c.chave);
            item.append(elemento('span', 'chave'), elemento('span', 'resumo__nome', c.nome), elemento('strong', '', duracao(minutos[c.chave])));
            lista.append(item);
        });
        var itemLivre = elemento('li', 'resumo__livre');
        itemLivre.append(elemento('span', 'chave chave--livre'), elemento('span', 'resumo__nome', 'Livre'), elemento('strong', '', duracao(livre)));
        lista.append(itemLivre);

        resumoEl.append(elemento('h3', 'resumo__titulo', 'Tempo do dia'), barra, lista);
    }

    /** Coloca blocos que se sobrepõem lado a lado: define .lane e .total em cada bloco. */
    function organizarColunas(blocos) {
        blocos.sort(function (a, b) { return a.ini - b.ini || a.fim - b.fim; });
        var grupo = [], fimDasColunas = [], fimDoGrupo = -1;

        function fechar() {
            grupo.forEach(function (b) { b.total = fimDasColunas.length; });
            grupo = []; fimDasColunas = []; fimDoGrupo = -1;
        }

        blocos.forEach(function (b) {
            if (grupo.length && b.ini >= fimDoGrupo) fechar();
            var coluna = 0;
            while (fimDasColunas[coluna] !== undefined && fimDasColunas[coluna] > b.ini) coluna++;
            fimDasColunas[coluna] = b.fim;
            b.coluna = coluna;
            grupo.push(b);
            fimDoGrupo = Math.max(fimDoGrupo, b.fim);
        });
        fechar();
    }

    function renderAgenda(dia, eventos) {
        agendaEl.textContent = '';

        if (!eventos.length) {
            var vazio = elemento('div', 'agenda__vazio');
            vazio.append(
                elemento('p', '', 'Nada planejado para este dia.'),
                elemento('p', 'nota', 'Reserve horários para estudo, trabalho, treino, refeições, sono e lazer.')
            );
            var adicionar = elemento('button', 'btn btn--ghost btn--sm', 'Adicionar evento');
            adicionar.type = 'button';
            adicionar.addEventListener('click', function () { abrirDialogo(null); });
            vazio.append(adicionar);
            agendaEl.append(vazio);
            return;
        }

        var grade = elemento('div', 'agenda__grade');
        grade.style.height = (24 * ALTURA_HORA) + 'px';

        for (var h = 0; h < 24; h++) {
            var linha = elemento('div', 'agenda__hora');
            linha.style.top = (h * ALTURA_HORA) + 'px';
            linha.append(elemento('span', '', dois(h) + ':00'));
            grade.append(linha);
        }

        var blocos = eventos.map(function (ev) {
            var r = recorteNoDia(ev, dia);
            return { ev: ev, ini: r[0], fim: r[1] };
        });
        organizarColunas(blocos);

        blocos.forEach(function (b) {
            var ev = b.ev;
            var altura = Math.max((b.fim - b.ini) / 60 * ALTURA_HORA, 22);
            var botao = elemento('button', 'agenda__evento ev-' + ev.categoria + (altura < 44 ? ' is-curto' : ''));
            botao.type = 'button';
            botao.style.top = (b.ini / 60 * ALTURA_HORA) + 'px';
            botao.style.height = altura + 'px';
            botao.style.left = 'calc(' + (b.coluna / b.total * 100) + '% + 2px)';
            botao.style.width = 'calc(' + (100 / b.total) + '% - 4px)';

            var horario = hhmm(ev.ini) + ' às ' + hhmm(ev.fim);
            var continuacao = ev.ini < dia ? ' (desde ontem)' : (ev.fim > somarDias(dia, 1) ? ' (continua amanhã)' : '');
            botao.append(elemento('strong', '', ev.titulo), elemento('span', '', horario.replace(' às ', '–') + continuacao));
            botao.title = ev.titulo + ' · ' + categoriaPor[ev.categoria].nome + ' · ' + horario + continuacao;
            botao.setAttribute('aria-label', categoriaPor[ev.categoria].nome + ': ' + ev.titulo + ', ' + horario + continuacao + '. Editar.');
            botao.addEventListener('click', function () { abrirDialogo(ev); });
            grade.append(botao);
        });

        // Linha do horário atual, quando o dia é hoje.
        if (iso(dia) === config.hoje) {
            var agora = new Date();
            var linhaAgora = elemento('div', 'agenda__agora');
            linhaAgora.style.top = ((agora.getHours() * 60 + agora.getMinutes()) / 60 * ALTURA_HORA) + 'px';
            grade.append(linhaAgora);
        }

        // Clicar num espaço vazio cria um evento naquela hora.
        grade.addEventListener('click', function (ev) {
            if (ev.target.closest('.agenda__evento')) return;
            var y = ev.clientY - grade.getBoundingClientRect().top;
            var hora = Math.min(23, Math.max(0, Math.floor(y / ALTURA_HORA)));
            abrirDialogo(null, { inicio: dois(hora) + ':00', fim: dois((hora + 1) % 24) + ':00' });
        });

        agendaEl.append(grade);

        // Rola até o primeiro compromisso (uma hora antes).
        var primeiro = Math.min.apply(null, blocos.map(function (b) { return b.ini; }));
        agendaEl.scrollTop = Math.max(0, (primeiro / 60 - 1) * ALTURA_HORA);
    }

    porId('novo-evento').addEventListener('click', function () { abrirDialogo(null); });

    // ---------- humor ----------
    var radiosHumor = Array.prototype.slice.call(document.querySelectorAll('#humor-opcoes input[name="humor"]'));
    var notaArea = porId('humor-nota-area');
    var notaCampo = porId('humor-nota');
    var estadoHumor = porId('humor-estado');

    function renderHumor() {
        var chave = estado.selecionado;
        var dia = lerIso(chave);
        var futuro = chave > config.hoje;
        var registro = dadosAtuais().humor[chave];

        porId('humor-data').textContent = (chave === config.hoje ? 'Hoje, ' : '') +
            DIAS[dia.getDay()] + ', ' + dia.getDate() + ' de ' + MESES[dia.getMonth()];

        radiosHumor.forEach(function (radio) {
            radio.disabled = futuro;
            radio.checked = !!registro && +radio.value === registro.nivel;
            radio.parentElement.classList.toggle('is-marcado', radio.checked);
            radio.parentElement.classList.toggle('is-desativado', futuro);
        });

        notaArea.hidden = !registro;
        notaCampo.value = registro ? registro.nota : '';

        estadoHumor.textContent = '';
        if (futuro) {
            estadoHumor.textContent = 'Você só pode registrar o humor de hoje e de dias anteriores.';
        } else if (registro) {
            var h = humorPor[registro.nivel];
            estadoHumor.append('Registrado para este dia: ' + h.emoji + ' ' + h.nome + '. ');
            var remover = elemento('button', 'link-botao', 'Remover registro');
            remover.type = 'button';
            remover.addEventListener('click', removerHumor);
            estadoHumor.append(remover);
        } else {
            estadoHumor.textContent = 'Toque em um emoji para registrar como você está neste dia.';
        }
    }

    function guardarHumorNoCache(chave, registro) {
        Object.keys(estado.dados).forEach(function (mes) {
            if (registro) estado.dados[mes].humor[chave] = registro;
            else delete estado.dados[mes].humor[chave];
        });
    }

    function salvarHumor(nivel, nota) {
        var chave = estado.selecionado;
        var dados = { data: chave, nivel: nivel };
        if (nota !== undefined) dados.nota = nota;

        api('humor', dados).then(function (r) {
            if (r.json.ok) {
                guardarHumorNoCache(chave, r.json.humor);
                renderCalendario();
                renderHumor();
                mostrarToast(nota !== undefined ? 'Anotação salva.' : humorPor[nivel].emoji + ' Humor registrado.');
            } else {
                mostrarToast(r.json.mensagem, true);
                renderHumor();
            }
        }).catch(falhaDeRede);
    }

    function removerHumor() {
        var chave = estado.selecionado;
        api('humor_excluir', { data: chave }).then(function (r) {
            if (r.json.ok) {
                guardarHumorNoCache(chave, null);
                renderCalendario();
                renderHumor();
                mostrarToast(r.json.mensagem);
            } else {
                mostrarToast(r.json.mensagem, true);
            }
        }).catch(falhaDeRede);
    }

    radiosHumor.forEach(function (radio) {
        radio.addEventListener('change', function () { salvarHumor(+radio.value); });
    });

    function salvarNota() {
        var registro = dadosAtuais().humor[estado.selecionado];
        if (registro) salvarHumor(registro.nivel, notaCampo.value.trim());
    }
    porId('humor-salvar-nota').addEventListener('click', salvarNota);
    notaCampo.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter') { ev.preventDefault(); salvarNota(); }
    });

    // ---------- diálogo de evento ----------
    var dialogo = porId('evento-dialogo');
    var form = porId('evento-form');
    var campos = {
        titulo: porId('ev-titulo'), data: porId('ev-data'), inicio: porId('ev-inicio'), fim: porId('ev-fim'),
        obs: porId('ev-obs'), repetir: porId('ev-repetir'), ate: porId('ev-ate')
    };
    var avisoErro = porId('evento-erro');
    var avisoConflito = porId('evento-conflito');
    var editando = null;      // evento em edição (null = novo)
    var tocou = { titulo: false, hora: false };

    function categoriaEscolhida() {
        var marcado = form.querySelector('input[name="categoria"]:checked');
        return marcado ? marcado.value : '';
    }

    function escolherCategoria(chave) {
        var radio = form.querySelector('input[name="categoria"][value="' + chave + '"]');
        if (radio) radio.checked = true;
    }

    function limparAvisos() {
        avisoErro.hidden = true;
        avisoConflito.hidden = true;
    }

    function atualizarDuracao() {
        var dica = porId('ev-duracao');
        var i = campos.inicio.value, f = campos.fim.value;
        if (!i || !f) { dica.textContent = ''; return; }
        if (i === f) { dica.textContent = 'O fim precisa ser diferente do início.'; return; }

        var minI = +i.slice(0, 2) * 60 + +i.slice(3), minF = +f.slice(0, 2) * 60 + +f.slice(3);
        var passaDeMeiaNoite = minF < minI;
        var total = passaDeMeiaNoite ? minF + 1440 - minI : minF - minI;
        dica.textContent = 'Duração: ' + duracao(total) + (passaDeMeiaNoite ? ' (termina no dia seguinte)' : '');
    }

    function abrirDialogo(evento, sugestao) {
        editando = evento;
        limparAvisos();
        sugestao = sugestao || {};

        porId('evento-titulo-dialogo').textContent = evento ? 'Editar evento' : 'Novo evento';
        porId('evento-salvar').textContent = evento ? 'Salvar alterações' : 'Salvar';
        porId('ev-repetir-area').hidden = !!evento;
        porId('ev-serie-aviso').hidden = !(evento && evento.serie);
        porId('evento-excluir-area').hidden = !evento;
        porId('evento-excluir-serie').hidden = !(evento && evento.serie);

        if (evento) {
            escolherCategoria(evento.categoria);
            campos.titulo.value = evento.titulo;
            campos.data.value = iso(evento.ini);
            campos.inicio.value = hhmm(evento.ini);
            campos.fim.value = hhmm(evento.fim);
            campos.obs.value = evento.observacao || '';
            tocou = { titulo: true, hora: true };
        } else {
            var padrao = categoriaPor.geral.padrao;
            escolherCategoria('geral');
            campos.titulo.value = padrao.titulo;
            campos.data.value = estado.selecionado;
            campos.inicio.value = sugestao.inicio || padrao.inicio;
            campos.fim.value = sugestao.fim || padrao.fim;
            campos.obs.value = '';
            campos.repetir.value = 'nenhuma';
            porId('ev-ate-campo').hidden = true;
            tocou = { titulo: false, hora: !!sugestao.inicio };
        }

        atualizarDuracao();
        dialogo.showModal();
        campos.titulo.focus();
    }

    function fecharDialogo() {
        if (dialogo.open) dialogo.close();
    }

    dialogo.addEventListener('click', function (ev) {
        // Clique no fundo escurecido (fora do formulário) fecha; fechar-botões também.
        if (ev.target === dialogo || ev.target.closest('[data-fechar]')) fecharDialogo();
    });

    // Ao trocar a categoria de um evento novo, sugere título e horários (se a pessoa ainda não mexeu neles).
    form.querySelectorAll('input[name="categoria"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (editando) return;
            var padrao = categoriaPor[radio.value].padrao;
            if (!tocou.titulo) campos.titulo.value = padrao.titulo;
            if (!tocou.hora) { campos.inicio.value = padrao.inicio; campos.fim.value = padrao.fim; }
            atualizarDuracao();
        });
    });
    campos.titulo.addEventListener('input', function () { tocou.titulo = true; });
    [campos.inicio, campos.fim].forEach(function (c) {
        c.addEventListener('input', function () { tocou.hora = true; atualizarDuracao(); });
    });

    campos.repetir.addEventListener('change', function () {
        var repete = campos.repetir.value !== 'nenhuma';
        porId('ev-ate-campo').hidden = !repete;
        if (repete && !campos.ate.value) {
            campos.ate.value = iso(somarDias(lerIso(campos.data.value || estado.selecionado), 28));
        }
    });

    function mostrarErro(mensagem) {
        avisoConflito.hidden = true;
        avisoErro.textContent = mensagem;
        avisoErro.hidden = false;
    }

    function mostrarConflito(json) {
        avisoErro.hidden = true;
        porId('evento-conflito-msg').textContent = json.mensagem;
        var lista = porId('evento-conflito-lista');
        lista.textContent = '';
        json.conflitos.forEach(function (c) {
            var ini = new Date(c.inicio), fim = new Date(c.fim);
            lista.append(elemento('li', '', c.titulo + ' · ' + ini.getDate() + '/' + dois(ini.getMonth() + 1) + ' ' + hhmm(ini) + '–' + hhmm(fim)));
        });
        avisoConflito.hidden = false;
        avisoConflito.scrollIntoView({ block: 'nearest' });
    }

    function enviar(forcar) {
        limparAvisos();

        var dados = {
            titulo: campos.titulo.value.trim(),
            categoria: categoriaEscolhida(),
            data: campos.data.value,
            hora_inicio: campos.inicio.value,
            hora_fim: campos.fim.value,
            observacao: campos.obs.value.trim(),
            forcar: forcar ? '1' : '0'
        };
        if (editando) {
            dados.id = editando.id;
        } else if (campos.repetir.value !== 'nenhuma') {
            dados.repetir = campos.repetir.value;
            dados.ate = campos.ate.value;
        }

        if (!dados.categoria) return mostrarErro('Escolha uma categoria.');
        if (!dados.titulo) { campos.titulo.focus(); return mostrarErro('Dê um título ao evento.'); }
        if (!dados.data || !dados.hora_inicio || !dados.hora_fim) return mostrarErro('Informe a data e os horários.');
        if (dados.hora_inicio === dados.hora_fim) return mostrarErro('O fim precisa ser diferente do início.');
        if (dados.repetir && !dados.ate) return mostrarErro('Escolha até quando repetir.');

        var botao = porId('evento-salvar');
        botao.disabled = true;
        api('salvar', dados).then(function (r) {
            if (r.json.ok) {
                fecharDialogo();
                estado.dados = {};
                selecionar(lerIso(dados.data));
                mostrarToast(r.json.mensagem);
            } else if (r.status === 409) {
                mostrarConflito(r.json);
            } else {
                mostrarErro(r.json.mensagem);
            }
        }).catch(function () {
            mostrarErro('Não foi possível conectar. Verifique sua conexão e tente de novo.');
        }).finally(function () { botao.disabled = false; });
    }

    form.addEventListener('submit', function (ev) { ev.preventDefault(); enviar(false); });
    porId('evento-forcar').addEventListener('click', function () { enviar(true); });

    function excluir(escopo) {
        var pergunta = escopo === 'serie'
            ? 'Excluir todos os eventos desta série?'
            : 'Excluir o evento “' + editando.titulo + '”?';
        if (!window.confirm(pergunta)) return;

        api('excluir', { id: editando.id, escopo: escopo }).then(function (r) {
            if (r.json.ok) {
                fecharDialogo();
                recarregar();
                mostrarToast(r.json.mensagem);
            } else {
                mostrarErro(r.json.mensagem);
            }
        }).catch(falhaDeRede);
    }
    porId('evento-excluir').addEventListener('click', function () { excluir('este'); });
    porId('evento-excluir-serie').addEventListener('click', function () { excluir('serie'); });

    // ---------- início ----------
    selecionar(hoje);
})();
