// Filtro por nome da lista de exercícios, ignorando acentos e maiúsculas.
(function () {
    var campo = document.getElementById('busca-exercicio');
    var cartoes = Array.prototype.slice.call(document.querySelectorAll('.exercicio'));
    var aviso = document.getElementById('sem-resultado');
    if (!campo) return;

    function normalizar(texto) {
        return texto.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }

    campo.addEventListener('input', function () {
        var termo = normalizar(campo.value.trim());
        var visiveis = 0;

        cartoes.forEach(function (cartao) {
            var mostrar = normalizar(cartao.dataset.nome).indexOf(termo) !== -1;
            cartao.hidden = !mostrar;
            if (mostrar) visiveis++;
        });

        aviso.hidden = visiveis !== 0;
    });
})();
