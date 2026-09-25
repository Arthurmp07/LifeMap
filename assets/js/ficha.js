// Ficha do paciente (profissional): desenha o gráfico de IMC do paciente.
// Os dados vêm do PHP em <script id="ficha-config" type="application/json">.
(function () {
    var config = JSON.parse(document.getElementById('ficha-config').textContent);
    var alvo = document.getElementById('ficha-grafico');
    if (!alvo || !window.GraficoIMC) return;

    var categorias = config.categorias.map(function (c) {
        return { chave: c.chave, nome: c.nome, limite: c.limite === null ? Infinity : c.limite };
    });
    function categoriaDe(imc) {
        return categorias.find(function (c) { return imc < c.limite; });
    }

    var desenhou = window.GraficoIMC.desenhar(alvo, config.registros, categoriaDe);
    if (!desenhou) {
        var aviso = document.createElement('p');
        aviso.className = 'hist__vazio';
        aviso.textContent = 'Com mais um registro de IMC, o gráfico da evolução aparece aqui.';
        alvo.append(aviso);
    }
})();
