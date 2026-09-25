<?php
// Proteção contra CSRF: um token por sessão, enviado em campo oculto
// (formulários) ou no cabeçalho X-CSRF-Token (fetch).

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valido(): bool
{
    $enviado = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($enviado) && $enviado !== '' && hash_equals(csrf_token(), $enviado);
}
