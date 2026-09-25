<?php
// Baixa um PDF com o perfil salvo do usuário logado, o treino e a dieta indicados.
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/pdf_perfil.php';

exigir_login();

$usuario = usuario_atual();
$planos = planos_do_perfil();
$avaliacoes = listar_avaliacoes((int) $usuario['id'], 2);

$conteudo = gerar_pdf_do_perfil([
    'usuario'    => $usuario,
    'ultimo_imc' => ultimo_imc((int) $usuario['id']),
    'avaliacao'  => $avaliacoes[0] ?? null,
    'avaliacao_anterior' => $avaliacoes[1] ?? null,
    'treino'     => $planos['treino'],
    'dieta'      => $planos['dieta'],
    'faltando'   => $planos['faltando'],
]);

// Nome do arquivo só com caracteres simples: LifeMap-perfil-marina-2026-09-25.pdf
$primeiroNome = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', explode(' ', trim($usuario['nome']))[0]) ?: 'usuario'));
$arquivo = 'LifeMap-perfil-' . trim($primeiroNome, '-') . '-' . date('Y-m-d') . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $arquivo . '"');
header('Content-Length: ' . strlen($conteudo));
header('Cache-Control: private, no-store');   // contém dados pessoais e de saúde
echo $conteudo;
