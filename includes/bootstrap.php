<?php
// Ponto de entrada comum: sessão, caminhos/URLs e helpers básicos.
// Toda página e todo processador começam incluindo este arquivo.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// O site é brasileiro: "hoje" e datas máximas seguem o horário de Brasília, e não o
// fuso padrão do PHP (que no XAMPP pode ser outro).
date_default_timezone_set('America/Sao_Paulo');

// Pasta raiz do projeto no disco.
define('BASE_PATH', dirname(__DIR__));

// Prefixo da URL do projeto (ex.: "/LifeMap"). Calculado a partir do
// DOCUMENT_ROOT, para funcionar em qualquer pasta. Pode ser forçado com a
// variável de ambiente LIFEMAP_BASE_URL.
(function (): void {
    $forcado = getenv('LIFEMAP_BASE_URL');
    if ($forcado !== false) {
        define('BASE_URL', rtrim($forcado, '/'));
        return;
    }

    $raiz = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $projeto = realpath(BASE_PATH) ?: BASE_PATH;
    $prefixo = ($raiz !== '' && stripos($projeto, $raiz) === 0)
        ? str_replace('\\', '/', substr($projeto, strlen($raiz)))
        : '';
    define('BASE_URL', rtrim($prefixo, '/'));
})();

/** URL de uma página do projeto: url('fisico/imc.php'). */
function url(string $caminho = ''): string
{
    return BASE_URL . '/' . ltrim($caminho, '/');
}

/** URL de um arquivo em assets/: asset('css/style.css'). */
function asset(string $caminho): string
{
    return url('assets/' . ltrim($caminho, '/'));
}

/** Escapa texto para HTML. */
function e(?string $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function redirecionar(string $destino): never
{
    header("Location: $destino");
    exit();
}

// Mensagens de uma única exibição (erro de login, sucesso de cadastro...).
function flash_set(string $chave, $valor): void
{
    $_SESSION['flash'][$chave] = $valor;
}

function flash_get(string $chave)
{
    $valor = $_SESSION['flash'][$chave] ?? null;
    unset($_SESSION['flash'][$chave]);
    return $valor;
}

require_once __DIR__ . '/saude.php';
require_once __DIR__ . '/validacao.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/planos.php';
require_once __DIR__ . '/rotina.php';
require_once __DIR__ . '/avaliacao.php';
