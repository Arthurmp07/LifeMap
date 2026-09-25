<?php
// Estado de login do visitante e controle de acesso por perfil (usuário, profissional, administrador).

const COLUNAS_USUARIO = 'id, nome, genero, data_nascimento, email, telefone, altura, objetivo, problema_saude, cadastro_completo, papel, ativo, trocar_senha';

const ROTULOS_PAPEL = [
    'usuario'      => 'Usuário',
    'profissional' => 'Profissional',
    'admin'        => 'Administrador',
];

function usuario_logado(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Dados do usuário logado, lidos do banco (uma vez por requisição).
 * Devolve null se ninguém estiver logado, se a conta deixou de existir ou se foi desativada.
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
            } elseif (!$usuario['ativo']) {
                $usuario = null;
                encerrar_sessao('Esta conta foi desativada. Fale com o administrador.');
            }
        }
    }

    return $usuario;
}

/** Nome para saudações, mantendo o título: "Dra. Paula Lima" -> "Dra. Paula"; "Ana Souza" -> "Ana". */
function nome_de_saudacao(string $nome): string
{
    $partes = preg_split('/\s+/u', trim($nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $saudacao = [];
    foreach ($partes as $parte) {
        $saudacao[] = $parte;
        if (!preg_match('/^(dr|dra|prof|profa|sr|sra)\.?$/iu', $parte)) {
            break;   // primeira palavra que não é título: é o primeiro nome
        }
    }
    return implode(' ', $saudacao);
}

/**
 * Perfil guardado na sessão, só para montar menus sem consultar o banco.
 * Quem decide o acesso é sempre o banco (usuario_atual()), nunca este valor.
 */
function papel_da_sessao(): string
{
    $papel = $_SESSION['papel'] ?? 'usuario';
    return isset(ROTULOS_PAPEL[$papel]) ? $papel : 'usuario';
}

/** Página inicial de cada perfil (para onde ele vai ao entrar). */
function inicio_do_papel(string $papel): string
{
    return match ($papel) {
        'profissional' => url('profissional/'),
        'admin'        => url('admin/'),
        default        => url(''),
    };
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

/**
 * Páginas de um ou mais perfis. Devolve os dados do usuário logado.
 *   - sem login ou conta desativada -> login
 *   - perfil diferente             -> página "sem acesso" (403)
 *   - senha provisória             -> obriga a trocar antes de continuar
 */
function exigir_papel(string ...$papeis): array
{
    exigir_login();

    $usuario = usuario_atual();
    if ($usuario === null) {
        // A conta foi desativada ou apagada: a sessão já foi encerrada.
        redirecionar(url('auth/login.php'));
    }

    if (!in_array($usuario['papel'], $papeis, true)) {
        acesso_negado($usuario['papel']);
    }

    if ($usuario['trocar_senha']) {
        flash_set('senha_aviso', 'Sua senha é provisória. Crie uma senha nova para continuar.');
        redirecionar(url('auth/alterar_senha.php'));
    }

    return $usuario;
}

/** Mostra a página de acesso negado e encerra. */
function acesso_negado(string $papel): never
{
    http_response_code(403);
    $pageTitle = 'Sem acesso';
    $paginaAtiva = '';
    $voltar = inicio_do_papel($papel);
    require BASE_PATH . '/partials/acesso_negado.php';
    exit();
}

/**
 * Guarda das APIs JSON: responde 401/403 em JSON e, depois de conferir, libera o lock da sessão
 * (chamadas de polling, como o chat, não podem ficar uma esperando a outra). $_SESSION segue
 * legível, mas as APIs não devem escrever nela.
 */
function exigir_papel_api(string ...$papeis): array
{
    $usuario = usuario_logado() ? usuario_atual() : null;
    if ($usuario === null) {
        api_erro(401, 'Entre na sua conta para continuar.');
    }
    if (!in_array($usuario['papel'], $papeis, true)) {
        api_erro(403, 'Você não tem acesso a este recurso.');
    }
    if ($usuario['trocar_senha']) {
        api_erro(403, 'Crie uma senha nova antes de continuar.');
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    return $usuario;
}

/** Destino pós-login: a página que pediu o login, se for deste projeto. */
function destino_depois_do_login(string $padrao = ''): string
{
    $destino = $_SESSION['depois_do_login'] ?? '';
    unset($_SESSION['depois_do_login']);

    $dentroDoProjeto = is_string($destino) && str_starts_with($destino, BASE_URL . '/');
    return $dentroDoProjeto ? $destino : ($padrao !== '' ? $padrao : url(''));
}

/** Encerra a sessão. Com $aviso, abre uma sessão vazia só para a tela de login mostrar o motivo. */
function encerrar_sessao(string $aviso = ''): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();

    if ($aviso !== '' && !headers_sent()) {
        // ID novo de propósito: se o PHP reaproveitasse o do navegador, não reenviaria o cookie
        // e o único Set-Cookie seria o que apaga a sessão (o aviso se perderia).
        session_id(bin2hex(random_bytes(16)));
        session_start();
        flash_set('login_erro', $aviso);
    }
}
