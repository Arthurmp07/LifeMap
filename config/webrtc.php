<?php
// Servidores usados pelo navegador para as chamadas de voz e vídeo acharem um caminho até o outro lado.
// O áudio e o vídeo vão direto de um navegador ao outro; o servidor do site só troca os "sinais".
//
// STUN: descobre o endereço público de cada lado (por padrão, os servidores públicos do Google; eles
//       enxergam o IP de quem liga, nada mais). Para usar outro: LIFEMAP_STUN_URLS="stun:host:3478,stun:outro:3478".
//       Para não usar nenhum (só funciona na mesma rede): LIFEMAP_STUN_URLS=none.
// TURN: retransmite a chamada quando a conexão direta é impossível (redes corporativas, 4G com CGNAT).
//       Opcional; é preciso ter um servidor TURN próprio ou contratado:
//       LIFEMAP_TURN_URL="turn:host:3478" LIFEMAP_TURN_USER=... LIFEMAP_TURN_PASS=...
$stun = getenv('LIFEMAP_STUN_URLS');
$stun = $stun === false || trim($stun) === ''
    ? ['stun:stun.l.google.com:19302', 'stun:stun1.l.google.com:19302']
    : (strtolower(trim($stun)) === 'none' ? [] : array_values(array_filter(array_map('trim', explode(',', $stun)))));

$servidores = $stun ? [['urls' => $stun]] : [];

if (getenv('LIFEMAP_TURN_URL')) {
    $servidores[] = [
        'urls'       => array_values(array_filter(array_map('trim', explode(',', getenv('LIFEMAP_TURN_URL'))))),
        'username'   => getenv('LIFEMAP_TURN_USER') ?: '',
        'credential' => getenv('LIFEMAP_TURN_PASS') ?: '',
    ];
}

return ['iceServers' => $servidores];
