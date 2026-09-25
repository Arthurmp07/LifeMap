// Gráfico de linha da evolução do IMC (SVG puro, sem bibliotecas).
//
//   GraficoIMC.desenhar(container, registros, categoriaDe)
//     registros:   [{ id, peso, altura, imc, data: "AAAA-MM-DDTHH:MM:SS" }, ...] (do mais antigo ao mais novo)
//     categoriaDe: função (imc) => { nome }
//
// Uma única série: sem legenda (o título do cartão já diz o que é). O valor
// exato aparece no último ponto, no tooltip (mouse, toque ou teclado) e na
// tabela de registros logo abaixo.
(function () {
    var NS = 'http://www.w3.org/2000/svg';
    var FAIXA_NORMAL = [18.5, 25];          // faixa de peso normal, desenhada como referência
    var MARGEM = { topo: 16, direita: 52, base: 34, esquerda: 40 };

    function svg(nome, atributos, pai) {
        var no = document.createElementNS(NS, nome);
        Object.keys(atributos || {}).forEach(function (k) { no.setAttribute(k, atributos[k]); });
        if (pai) pai.appendChild(no);
        return no;
    }

    function html(nome, classe, texto) {
        var no = document.createElement(nome);
        if (classe) no.className = classe;
        if (texto !== undefined) no.textContent = texto;
        return no;
    }

    function numero(n, casas) { return n.toFixed(casas).replace('.', ','); }
    function dois(n) { return (n < 10 ? '0' : '') + n; }
    function dataCurta(d, comAno) {
        return dois(d.getDate()) + '/' + dois(d.getMonth() + 1) + (comAno ? '/' + String(d.getFullYear()).slice(2) : '');
    }
    function dataCompleta(d) {
        return dataCurta(d) + '/' + d.getFullYear() + ' ' + dois(d.getHours()) + ':' + dois(d.getMinutes());
    }

    // Menor passo "redondo" que mantém o número de marcas dentro do limite.
    function passo(intervalo, maximoDeMarcas) {
        var candidatos = [0.5, 1, 2, 5, 10];
        for (var i = 0; i < candidatos.length; i++) {
            if (intervalo / candidatos[i] <= maximoDeMarcas) return candidatos[i];
        }
        return 10;
    }

    function desenhar(container, registros, categoriaDe) {
        container.textContent = '';
        if (registros.length < 2) return false;

        var area = html('div', 'grafico__area');
        var dica = html('div', 'grafico__dica');
        dica.hidden = true;
        container.append(area, dica);

        var pontos = registros.map(function (r) {
            var d = new Date(r.data);
            return { r: r, d: d, t: d.getTime(), imc: r.imc };
        });
        var ultimo = pontos[pontos.length - 1];
        var primeiro = pontos[0];

        var larguraAnterior = 0;

        function montar() {
            var W = Math.max(area.clientWidth, 280);
            var H = W < 480 ? 220 : 280;
            larguraAnterior = W;
            area.textContent = '';

            // --- escalas ---
            var valores = pontos.map(function (p) { return p.imc; }).concat(FAIXA_NORMAL);
            var minimo = Math.floor(Math.min.apply(null, valores) - 1);
            var maximo = Math.ceil(Math.max.apply(null, valores) + 1);
            var p = passo(maximo - minimo, 6);
            minimo = Math.floor(minimo / p) * p;
            maximo = Math.ceil(maximo / p) * p;

            var xEsq = MARGEM.esquerda + 16;
            var xDir = W - MARGEM.direita;
            var yTopo = MARGEM.topo;
            var yBase = H - MARGEM.base;
            var mesmoInstante = ultimo.t === primeiro.t;

            function x(ponto, i) {
                if (mesmoInstante) return xEsq + (xDir - xEsq) * (i / (pontos.length - 1));
                return xEsq + (ponto.t - primeiro.t) / (ultimo.t - primeiro.t) * (xDir - xEsq);
            }
            function y(valor) { return yTopo + (maximo - valor) / (maximo - minimo) * (yBase - yTopo); }

            var el = svg('svg', {
                viewBox: '0 0 ' + W + ' ' + H, width: W, height: H, role: 'img',
                'aria-label': 'Gráfico de linha da evolução do IMC, de ' + numero(primeiro.imc, 1) + ' em ' + dataCurta(primeiro.d) +
                    ' para ' + numero(ultimo.imc, 1) + ' em ' + dataCurta(ultimo.d) + '. Os valores estão na tabela abaixo.'
            }, area);

            // --- faixa de peso normal (referência) ---
            var yFaixaTopo = y(FAIXA_NORMAL[1]);
            var yFaixaBase = y(FAIXA_NORMAL[0]);
            svg('rect', { class: 'grafico__faixa', x: MARGEM.esquerda, y: yFaixaTopo, width: xDir - MARGEM.esquerda + 8, height: yFaixaBase - yFaixaTopo }, el);
            if (yFaixaBase - yFaixaTopo >= 26) {
                // Na base da faixa, onde a linha costuma passar menos; texto curto em telas estreitas.
                var rotuloFaixa = svg('text', { class: 'grafico__texto', x: MARGEM.esquerda + 8, y: yFaixaBase - 8 }, el);
                rotuloFaixa.textContent = W < 480 ? 'Faixa normal' : 'Faixa de peso normal (18,5 a 24,9)';
            }

            // --- grade e eixo Y ---
            for (var v = minimo; v <= maximo + 1e-9; v += p) {
                svg('line', { class: 'grafico__grade', x1: MARGEM.esquerda, x2: xDir + 8, y1: y(v), y2: y(v) }, el);
                var tickY = svg('text', { class: 'grafico__texto', x: MARGEM.esquerda - 8, y: y(v) + 4, 'text-anchor': 'end' }, el);
                tickY.textContent = p < 1 ? numero(v, 1) : String(Math.round(v));
            }

            // --- eixo X: primeira e última data, mais até 3 intermediárias ---
            var comAno = (ultimo.t - primeiro.t) > 200 * 86400000;
            var nMarcas = Math.min(5, Math.max(2, Math.floor((xDir - xEsq) / 90) + 1));
            var usadas = {};
            for (var i = 0; i < nMarcas; i++) {
                var alvo = i / (nMarcas - 1);
                var xt = xEsq + alvo * (xDir - xEsq);
                var instante = mesmoInstante ? primeiro.t : primeiro.t + alvo * (ultimo.t - primeiro.t);
                var rotulo = dataCurta(new Date(instante), comAno);
                if (usadas[rotulo]) continue;
                usadas[rotulo] = true;
                var tickX = svg('text', { class: 'grafico__texto', x: xt, y: H - 10, 'text-anchor': 'middle' }, el);
                tickX.textContent = rotulo;
            }

            // --- linha ---
            var caminho = pontos.map(function (pt, i) { return (i ? 'L' : 'M') + x(pt, i).toFixed(1) + ',' + y(pt.imc).toFixed(1); }).join(' ');
            svg('path', { class: 'grafico__linha', d: caminho }, el);

            // --- cursor e anel de foco (aparecem no hover/foco) ---
            var cursor = svg('line', { class: 'grafico__cursor', y1: yTopo, y2: yBase, visibility: 'hidden' }, el);
            var anel = svg('circle', { class: 'grafico__foco', r: 8, visibility: 'hidden' }, el);

            // --- pontos ---
            pontos.forEach(function (pt, i) {
                svg('circle', { class: 'grafico__ponto', cx: x(pt, i), cy: y(pt.imc), r: 4 }, el);
            });

            // --- rótulo só no ponto final ---
            var rotuloFinal = svg('text', { class: 'grafico__rotulo', x: x(ultimo, pontos.length - 1) + 12, y: y(ultimo.imc) + 5 }, el);
            rotuloFinal.textContent = numero(ultimo.imc, 1);

            // --- interação ---
            function mostrar(i) {
                var pt = pontos[i];
                var px = x(pt, i);
                var py = y(pt.imc);

                cursor.setAttribute('x1', px); cursor.setAttribute('x2', px); cursor.setAttribute('visibility', 'visible');
                anel.setAttribute('cx', px); anel.setAttribute('cy', py); anel.setAttribute('visibility', 'visible');

                dica.textContent = '';
                var linha1 = html('div', 'grafico__dica-valor');
                linha1.append(html('span', 'grafico__chave'), html('strong', '', numero(pt.imc, 1)), html('span', 'grafico__dica-cat', categoriaDe(pt.imc).nome));
                dica.append(
                    linha1,
                    html('div', 'grafico__dica-sec', dataCompleta(pt.d)),
                    html('div', 'grafico__dica-sec', numero(pt.r.peso, pt.r.peso % 1 ? 1 : 0) + ' kg · ' + numero(pt.r.altura, 2) + ' m')
                );
                dica.hidden = false;

                var caixa = area.getBoundingClientRect();
                var pai = container.getBoundingClientRect();
                var esquerda = (caixa.left - pai.left) + px + 14;
                if (esquerda + dica.offsetWidth > pai.width) esquerda = (caixa.left - pai.left) + px - 14 - dica.offsetWidth;
                dica.style.left = Math.max(0, esquerda) + 'px';
                dica.style.top = Math.max(0, (caixa.top - pai.top) + py - dica.offsetHeight - 12) + 'px';
            }

            function esconder() {
                cursor.setAttribute('visibility', 'hidden');
                anel.setAttribute('visibility', 'hidden');
                dica.hidden = true;
            }

            // O ponteiro só precisa estar perto do X: o mais próximo é escolhido.
            var alvoDoMouse = svg('rect', { x: MARGEM.esquerda, y: yTopo, width: xDir - MARGEM.esquerda + 8, height: yBase - yTopo, fill: 'transparent' }, el);
            alvoDoMouse.addEventListener('pointermove', function (ev) {
                var caixa = el.getBoundingClientRect();
                var px = (ev.clientX - caixa.left) * (W / caixa.width);
                var melhor = 0, distancia = Infinity;
                pontos.forEach(function (pt, i) {
                    var d = Math.abs(x(pt, i) - px);
                    if (d < distancia) { distancia = d; melhor = i; }
                });
                mostrar(melhor);
            });
            el.addEventListener('pointerleave', function (ev) { if (ev.pointerType === 'mouse') esconder(); });

            // Alvos maiores que a marca (24px+) e focáveis por teclado.
            pontos.forEach(function (pt, i) {
                var alvo = svg('circle', {
                    class: 'grafico__alvo', cx: x(pt, i), cy: y(pt.imc), r: 14, tabindex: 0, role: 'img',
                    'aria-label': dataCompleta(pt.d) + ': IMC ' + numero(pt.imc, 1) + ', ' + categoriaDe(pt.imc).nome
                }, el);
                alvo.addEventListener('pointerenter', function () { mostrar(i); });
                alvo.addEventListener('focus', function () { mostrar(i); });
                alvo.addEventListener('blur', esconder);
            });
        }

        montar();

        // Redesenha ao mudar a largura (rotação do celular, redimensionar a janela).
        if (window.ResizeObserver) {
            new ResizeObserver(function () {
                if (Math.abs(area.clientWidth - larguraAnterior) > 2) montar();
            }).observe(area);
        }

        // Toque fora do gráfico fecha o tooltip.
        document.addEventListener('pointerdown', function (ev) {
            if (!container.contains(ev.target)) dica.hidden = true;
        });

        return true;
    }

    window.GraficoIMC = { desenhar: desenhar, numero: numero, dataCurta: dataCurta, dataCompleta: dataCompleta };
})();
