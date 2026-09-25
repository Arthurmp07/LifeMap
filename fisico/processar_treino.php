<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    guardar_plano_do_formulario('treino');
    redirecionar(url('fisico/treino.php') . '#resultado');
}

redirecionar(url('fisico/treino.php'));
