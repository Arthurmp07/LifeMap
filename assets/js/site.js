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
