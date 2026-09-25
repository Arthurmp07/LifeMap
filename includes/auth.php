<?php
// Estado de login do visitante.

const COLUNAS_USUARIO = 'id, nome, genero, data_nascimento, email, telefone, altura, objetivo, problema_saude, cadastro_completo';

function usuario_logado(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Dados do usuário logado, lidos do banco (uma vez por requisição).
 * Devolve null se ninguém estiver logado ou se a conta deixou de existir.
 */
function usuario_atual(): ?array
{
    static $usuario = false;

    if ($usuario === false) {
        $usuario = null;
        if (usuario_logado()) {
            $stmt = db()->prepare('SELECT ' . COLUNAS_USUARIO . ' FROM usuarios WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $usuario = $stmt->fetch() ?: null;

            if ($usuario === null) {
                encerrar_sessao();
            }
        }
    }

    return $usuario;
}

/** Páginas restritas: manda para o login e volta para cá depois. */
function exigir_login(): void
{
    if (!usuario_logado()) {
        $_SESSION['depois_do_login'] = $_SERVER['REQUEST_URI'] ?? '';
        flash_set('login_erro', 'Entre na sua conta para acessar esta página.');
        redirecionar(url('auth/login.php'));
    }
}

/** Destino pós-login: a página que pediu o login, se for deste projeto. */
function destino_depois_do_login(): string
{
    $destino = $_SESSION['depois_do_login'] ?? '';
    unset($_SESSION['depois_do_login']);

    $dentroDoProjeto = is_string($destino) && str_starts_with($destino, BASE_URL . '/');
    return $dentroDoProjeto ? $destino : url('');
}

function encerrar_sessao(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
