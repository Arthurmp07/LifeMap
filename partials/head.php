<?php
// Abre o documento HTML. Cada página define $pageTitle (e, opcionalmente,
// $pageDescription e $bodyClass) antes de incluir este arquivo.
require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = $pageTitle ?? '';
$pageDescription = $pageDescription ?? 'Cuide do corpo, da mente e da alimentação em um só lugar: IMC, treinos, dietas e avaliação física.';
$tituloCompleto = $pageTitle !== '' ? "$pageTitle · LifeMap" : 'LifeMap';
$versaoCss = @filemtime(BASE_PATH . '/assets/css/style.css') ?: 1;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="#2F7D6B">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($tituloCompleto) ?></title>
    <link rel="icon" type="image/png" href="<?= asset('img/marca/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('img/marca/apple-touch-icon.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=<?= (int) $versaoCss ?>">
</head>
<body<?= !empty($bodyClass) ? ' class="' . e($bodyClass) . '"' : '' ?>>
