<?php
// Ações do atendimento feitas por formulário (POST + CSRF), para usuário e profissional:
//   profissional: convidar, cancelar_convite     usuário: aceitar, recusar     os dois: encerrar
require_once __DIR__ . '/../includes/bootstrap.php';

$pessoa = exigir_papel('usuario', 'profissional');
$ehProfissional = $pessoa['papel'] === 'profissional';

// Para onde voltar: a tela de quem fez a ação (nunca um endereço vindo do formulário).
$volta = $ehProfissional ? url('profissional/') : url('atendimento/');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash_set('painel_erros', ['Sessão expirada. Tente de novo.']);
    redirecionar($volta);
}

$acao = $_POST['acao'] ?? '';
$id = (int) ($_POST['id'] ?? 0);

switch ($acao) {
    case 'convidar':
        if ($ehProfissional) {
            [$ok, $mensagem] = convidar_usuario((int) $pessoa['id'], (int) ($_POST['usuario_id'] ?? 0));
            flash_set($ok ? 'painel_ok' : 'painel_erros', $ok ? $mensagem : [$mensagem]);
        }
        break;

    case 'cancelar_convite':
        if ($ehProfissional) {
            if (cancelar_convite($id, (int) $pessoa['id'])) {
                flash_set('painel_ok', 'Convite cancelado.');
            } else {
                flash_set('painel_erros', ['Este convite não existe mais ou já foi respondido.']);
            }
        }
        break;

    case 'aceitar':
    case 'recusar':
        if (!$ehProfissional) {
            $aceitar = $acao === 'aceitar';
            if (responder_convite($id, (int) $pessoa['id'], $aceitar)) {
                flash_set('painel_ok', $aceitar
                    ? 'Atendimento iniciado. O profissional já pode ver seus dados e conversar com você.'
                    : 'Convite recusado. O profissional não tem acesso aos seus dados.');
            } else {
                flash_set('painel_erros', ['Este convite não está mais disponível.']);
            }
        }
        break;

    case 'encerrar':
        if (encerrar_atendimento($id, (int) $pessoa['id'])) {
            flash_set('painel_ok', $ehProfissional
                ? 'Atendimento encerrado. Você não vê mais os dados dessa pessoa.'
                : 'Atendimento encerrado. O profissional não vê mais seus dados. A conversa continua guardada só para leitura.');
        } else {
            flash_set('painel_erros', ['Este atendimento não está mais em andamento.']);
        }
        break;

    default:
        flash_set('painel_erros', ['Ação inválida.']);
}

// Depois de convidar, a busca continua na tela (para convidar mais gente da mesma busca).
$busca = $ehProfissional && $acao === 'convidar' ? trim((string) ($_POST['busca'] ?? '')) : '';
redirecionar($volta . ($busca !== '' ? '?busca=' . rawurlencode(mb_substr($busca, 0, 100)) : ''));
