<?php
// Ações do painel do administrador (formulários com POST + CSRF).
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/profissionais.php';

$admin = exigir_papel('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash_set('painel_erros', ['Sessão expirada. Tente de novo.']);
    redirecionar(url('admin/'));
}

$acao = $_POST['acao'] ?? '';
$id = (int) ($_POST['id'] ?? 0);

try {
    switch ($acao) {
        case 'criar':
            [$dados, $erros] = validar_novo_profissional($_POST);
            if (!$erros) {
                [$senha, $erro] = cadastrar_profissional($dados);
                if ($erro === null) {
                    // A senha aparece uma única vez, na próxima tela.
                    flash_set('admin_senha', ['nome' => $dados['nome'], 'email' => $dados['email'], 'senha' => $senha, 'quando' => 'cadastrado']);
                    break;
                }
                $erros[] = $erro;
            }
            flash_set('painel_erros', $erros);
            flash_set('admin_old', $dados);
            break;

        case 'desativar':
        case 'reativar':
            $ativar = $acao === 'reativar';
            if (definir_profissional_ativo($id, $ativar, (int) $admin['id'])) {
                flash_set('painel_ok', $ativar
                    ? 'Profissional reativado. Ele volta a poder entrar e convidar usuários.'
                    : 'Profissional desativado. Ele não consegue mais entrar e os atendimentos dele foram encerrados.');
            } else {
                flash_set('painel_erros', ['Profissional não encontrado.']);
            }
            break;

        case 'nova_senha':
            $senha = redefinir_senha_do_profissional($id);
            if ($senha === null) {
                flash_set('painel_erros', ['Profissional não encontrado.']);
                break;
            }
            $stmt = db()->prepare('SELECT nome, email FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
            $p = $stmt->fetch();
            flash_set('admin_senha', ['nome' => $p['nome'], 'email' => $p['email'], 'senha' => $senha, 'quando' => 'redefinida']);
            break;

        default:
            flash_set('painel_erros', ['Ação inválida.']);
    }
} catch (PDOException $e) {
    error_log('Erro no painel do administrador: ' . $e->getMessage());
    flash_set('painel_erros', ['Não foi possível concluir agora. Tente novamente em instantes.']);
}

redirecionar(url('admin/'));
