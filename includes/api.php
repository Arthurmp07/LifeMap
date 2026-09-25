<?php
// Respostas JSON das APIs (fisico/*_api.php, rotina/api.php e as de atendimento têm as suas
// próprias funções locais; as novas usam estas).

/** Cabeçalhos comuns: JSON, sem cache. */
function api_cabecalhos(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
}

function api_responder(int $status, array $dados = []): never
{
    api_cabecalhos();
    http_response_code($status);
    echo json_encode(['ok' => $status >= 200 && $status < 300] + $dados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit();
}

function api_erro(int $status, string $mensagem): never
{
    api_responder($status, ['mensagem' => $mensagem]);
}

/** Exige POST com token CSRF válido. */
function api_exigir_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        api_erro(405, 'Método não permitido.');
    }
    if (!csrf_valido()) {
        api_erro(403, 'Sessão expirada. Recarregue a página e tente de novo.');
    }
}
