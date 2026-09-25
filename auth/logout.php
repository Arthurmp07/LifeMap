<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Só encerra a sessão via POST com token válido (botão "Sair" do cabeçalho).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido()) {
    encerrar_sessao();
}

redirecionar(url(''));
