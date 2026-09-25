<?php
// API JSON do atendimento (usuário e profissional).
//   GET  ?acao=resumo                              -> números do menu (convites, mensagens não lidas) e chamada tocando
//   GET  ?acao=mensagens&id=&depois=&antes=        -> mensagens do chat (abertura, novas ou página anterior)
//   POST acao=enviar   id, texto                   -> envia uma mensagem
//   POST acao=lidas    id                          -> marca como lidas as mensagens da outra pessoa
// Os POSTs exigem o token CSRF (cabeçalho X-CSRF-Token). Só participa quem faz parte do atendimento.
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/chat.php';
require_once __DIR__ . '/../includes/chamada.php';

// Esta API é chamada de tempos em tempos (polling): a guarda já libera o lock da sessão.
$eu = exigir_papel_api('usuario', 'profissional');
$euId = (int) $eu['id'];

$acao = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['acao'] ?? '') : ($_GET['acao'] ?? '');
$origem = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

/** Atendimento em que a pessoa participa e que já teve conversa (ativo ou encerrado); senão 404. */
function atendimento_da_conversa(array $origem, int $euId): array
{
    $atendimento = atendimento_do_participante((int) ($origem['id'] ?? 0), $euId);
    if (!$atendimento || !in_array($atendimento['status'], ['ativo', 'encerrado'], true)) {
        api_erro(404, 'Conversa não encontrada.');
    }
    return $atendimento;
}

try {
    switch ($acao) {
        case 'resumo':
            api_responder(200, ['resumo' => resumo_de_avisos($euId, $eu['papel']) + ['chamada' => chamada_entrando_para($euId)]]);

        case 'mensagens':
            $atendimento = atendimento_da_conversa($origem, $euId);
            $depois = max(0, (int) ($origem['depois'] ?? 0));
            $antes = max(0, (int) ($origem['antes'] ?? 0));
            [$mensagens, $temMais] = listar_mensagens((int) $atendimento['id'], $euId, $depois, $antes);
            api_responder(200, [
                'mensagens'      => $mensagens,
                'tem_mais'       => $temMais,
                'ativo'          => $atendimento['status'] === 'ativo',
                'lida_ate'       => ultima_mensagem_lida_pelo_outro((int) $atendimento['id'], $euId),
            ]);

        case 'enviar':
            api_exigir_post();
            $atendimento = atendimento_da_conversa($origem, $euId);
            [$mensagem, $erro, $status] = enviar_mensagem($atendimento, $euId, (string) ($_POST['texto'] ?? ''));
            if ($mensagem === null) {
                api_erro($status, $erro);
            }
            api_responder(200, ['mensagem' => $mensagem]);

        case 'lidas':
            api_exigir_post();
            $atendimento = atendimento_da_conversa($origem, $euId);
            api_responder(200, ['marcadas' => marcar_mensagens_lidas((int) $atendimento['id'], $euId)]);

        default:
            api_erro(400, 'Ação inválida.');
    }
} catch (PDOException $e) {
    error_log('Erro na API de atendimento: ' . $e->getMessage());
    api_erro(500, 'Não foi possível concluir agora. Tente novamente em instantes.');
}
