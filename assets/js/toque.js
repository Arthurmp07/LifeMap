// Toque de chamada: dois bipes a cada 2,5 s enquanto uma chamada está tocando, mais a vibração no celular.
// Os navegadores só liberam som depois de a pessoa interagir com a página; sem isso o toque fica em silêncio
// (o aviso na tela e o título da aba continuam).
(function () {
    var contexto = null;
    var timer = null;

    function bipes() {
        try {
            if (!contexto) contexto = new (window.AudioContext || window.webkitAudioContext)();
            if (contexto.state === 'suspended') contexto.resume();
            if (contexto.state !== 'running') return;
            var t0 = contexto.currentTime;
            [0, 0.34].forEach(function (atraso) {
                var osc = contexto.createOscillator();
                var ganho = contexto.createGain();
                osc.type = 'sine';
                osc.frequency.value = 523;
                ganho.gain.setValueAtTime(0.0001, t0 + atraso);
                ganho.gain.exponentialRampToValueAtTime(0.12, t0 + atraso + 0.03);
                ganho.gain.exponentialRampToValueAtTime(0.0001, t0 + atraso + 0.28);
                osc.connect(ganho);
                ganho.connect(contexto.destination);
                osc.start(t0 + atraso);
                osc.stop(t0 + atraso + 0.3);
            });
        } catch (e) { /* sem áudio: segue em silêncio */ }
        try { if (navigator.vibrate) navigator.vibrate([250, 120, 250]); } catch (e) { /* sem vibração */ }
    }

    window.LifeMapToque = {
        iniciar: function () { if (timer) return; bipes(); timer = setInterval(bipes, 2500); },
        parar: function () { clearInterval(timer); timer = null; try { if (navigator.vibrate) navigator.vibrate(0); } catch (e) { /* nada */ } }
    };
})();
