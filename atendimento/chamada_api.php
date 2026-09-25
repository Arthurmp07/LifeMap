<?php
// API JSON das chamadas de voz e vídeo (usuário e profissional do mesmo atendimento).
//   GET  ?acao=estado&id=<atendimento>&chamada=<id, opcional>&depois=<último sinal visto>
//                                   -> chamada em curso (ou a pedida) e os sinais novos do outro lado
//   POST acao=iniciar  id, video=0|1 -> liga
//   POST acao=atender  id, chamada   -> atende
//   POST acao=encerrar id, chamada   -> desliga (cancela, recusa ou encerra, conforme o momento)
//   POST acao=sinal    id, chamada, tipo=offer|answer|ice, dados=<JSON> -> envia um sinal WebRTC
// Os POSTs exigem o token CSRF (cabeçalho X-CSRF-Token ou campo "csrf", usado no envio ao fechar a aba).
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/chamada.php';

// API de polling: a guarda já libera o lock da sessão.
$eu = exigir_papel_api('usuario', 'profissional');
$euId = (int) $eu['id'];

$acao = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['acao'] ?? '') : ($_GET['acao'] ?? '');
$origem = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

/** Atendimento (ativo ou já encerrado) em que a pessoa participa; senão 404. */
function atendimento_da_chamada(array $origem, int $euId): array
{
    $atendimento = atendimento_do_participante((int) ($origem['id'] ?? 0), $euId);
    if (!$atendimento || !in_array($atendimento['status'], ['ativo', 'encerrado'], true)) {
        api_erro(404, 'Atendimento não encontrado.');
    }
    return $atendimento;
}

/** A chamada pedida, que precisa ser deste atendimento; senão 404. */
function chamada_pedida(array $origem, array $atendimento): array
{
    $chamada = chamada_por_id((int) ($origem['chamada'] ?? 0));
    if (!$chamada || (int) $chamada['atendimento_id'] !== (int) $atendimento['id']) {
        api_erro(404, 'Chamada não encontrada.');
    }
    return $chamada;
}

try {
    switch ($acao) {
        case 'estado':
            $atendimento = atendimento_da_chamada($origem, $euId);
            expirar_chamadas((int) $atendimento['id']);

            $chamada = (int) ($origem['chamada'] ?? 0) > 0 ? chamada_pedida($origem, $atendimento) : chamada_em_curso((int) $atendimento['id']);
            if (!$chamada) {
                api_responder(200, ['chamada' => null, 'sinais' => []]);
            }

            $emCurso = in_array($chamada['status'], ['tocando', 'em_andamento'], true);
            if ($emCurso) {
                bater_ponto_da_chamada($chamada, $euId);
            }
            api_responder(200, [
                'chamada' => chamada_para_json($chamada, $euId),
                'sinais'  => $emCurso ? sinais_do_outro((int) $chamada['id'], $euId, max(0, (int) ($origem['depois'] ?? 0))) : [],
            ]);

        case 'iniciar':
            api_exigir_post();
            $atendimento = atendimento_da_chamada($origem, $euId);
            [$chamada, $erro, $status] = iniciar_chamada($atendimento, $euId, ($_POST['video'] ?? '1') !== '0');
            if ($chamada === null) {
                api_erro($status, $erro);
            }
            api_responder(200, ['chamada' => $chamada]);

        case 'atender':
            api_exigir_post();
            $atendimento = atendimento_da_chamada($origem, $euId);
            expirar_chamadas((int) $atendimento['id']);
            $chamada = chamada_pedida($origem, $atendimento);
            if (!atendimento_permite_chamada($atendimento) || !atender_chamada($chamada, $euId)) {
                api_erro(409, 'Esta chamada não está mais tocando.');
            }
            api_responder(200, ['chamada' => chamada_para_json(chamada_por_id((int) $chamada['id']), $euId)]);

        case 'encerrar':
            api_exigir_post();
            $atendimento = atendimento_da_chamada($origem, $euId);
            $chamada = chamada_pedida($origem, $atendimento);
            $novo = encerrar_chamada($chamada, $euId);
            if ($novo === null) {
                api_erro(409, 'Esta chamada já terminou.');
            }
            api_responder(200, ['status' => $novo]);

        case 'sinal':
            api_exigir_post();
            $atendimento = atendimento_da_chamada($origem, $euId);
            $chamada = chamada_pedida($origem, $atendimento);
            $tipo = (string) ($_POST['tipo'] ?? '');
            if (!in_array($tipo, ['offer', 'answer', 'ice'], true)) {
                api_erro(422, 'Tipo de sinal inválido.');
            }
            [$erro, $status] = guardar_sinal($chamada, $euId, $tipo, (string) ($_POST['dados'] ?? ''));
            if ($erro !== null) {
                api_erro($status, $erro);
            }
            api_responder(200);

        default:
            api_erro(400, 'Ação inválida.');
    }
} catch (PDOException $e) {
    error_log('Erro na API de chamadas: ' . $e->getMessage());
    api_erro(500, 'Não foi possível concluir agora. Tente novamente em instantes.');
}
