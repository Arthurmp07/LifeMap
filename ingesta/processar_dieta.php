<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    guardar_plano_do_formulario('dieta');
    redirecionar(url('ingesta/dieta.php') . '#resultado');
}

redirecionar(url('ingesta/dieta.php'));
