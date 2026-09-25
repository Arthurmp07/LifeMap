<?php
// Chamadas de voz e vídeo do atendimento. O áudio e o vídeo NÃO passam pelo servidor (WebRTC direto entre
// os dois navegadores); aqui ficam o estado da chamada e os "sinais" que os navegadores trocam para se
// conectar (oferta, resposta e candidatos ICE).
//
//   iniciar (quem liga) -> tocando -> atender (o outro) -> em_andamento -> encerrar (qualquer um)
//                                  \-> recusar / cancelar / perdida (ninguém atendeu em 45 s)
//
// Cada lado "bate o ponto" toda vez que consulta a chamada (visto_*_em). Se um lado some (aba fechada,
// internet caiu), o servidor encerra a chamada sozinho.

const CHAMADA_TOQUE_SEGUNDOS = 45;        // ninguém atendeu: chamada perdida
const CHAMADA_SUMIU_TOCANDO = 20;         // quem ligou sumiu enquanto tocava: cancelada
const CHAMADA_SUMIU_EM_ANDAMENTO = 30;    // um lado sumiu durante a conversa: encerrada
const CHAMADA_SINAIS_MAX = 400;           // por chamada (uma chamada normal usa algumas dezenas)
const CHAMADA_SINAL_MAX_BYTES = 16384;    // um SDP com vídeo tem 5 a 9 KB

/** "Chamada de vídeo" / "Chamada de voz". */
function nome_da_chamada(bool $comVideo): string
{
    return $comVideo ? 'Chamada de vídeo' : 'Chamada de voz';
}

/** 75 -> "1 min 15 s"; 3725 -> "1 h 2 min". */
function formatar_duracao(int $segundos): string
{
    if ($segundos < 60) {
        return $segundos . ' s';
    }
    if ($segundos < 3600) {
        return intdiv($segundos, 60) . ' min ' . ($segundos % 60) . ' s';
    }
    return intdiv($segundos, 3600) . ' h ' . intdiv($segundos % 3600, 60) . ' min';
}

/**
 * Fecha as chamadas que ficaram sem ninguém: tocaram demais (perdida), quem ligou sumiu (cancelada) ou um
 * dos lados sumiu no meio da conversa (encerrada). Cada uma vira um aviso no chat.
 * Barato o bastante para rodar a cada consulta.
 */
function expirar_chamadas(int $atendimentoId = 0): void
{
    $pdo = db();
    $filtro = $atendimentoId > 0 ? 'AND atendimento_id = ' . (int) $atendimentoId : '';

    $stmt = $pdo->query(
        "SELECT id, atendimento_id, iniciador_id, com_video, status, atendida_em,
                CASE
                  WHEN status = 'tocando' AND criado_em < NOW() - INTERVAL " . CHAMADA_TOQUE_SEGUNDOS . " SECOND THEN 'perdida'
                  WHEN status = 'tocando' AND COALESCE(visto_iniciador_em, criado_em) < NOW() - INTERVAL " . CHAMADA_SUMIU_TOCANDO . " SECOND THEN 'cancelada'
                  WHEN status = 'em_andamento' AND (COALESCE(visto_iniciador_em, atendida_em) < NOW() - INTERVAL " . CHAMADA_SUMIU_EM_ANDAMENTO . " SECOND
                                                 OR COALESCE(visto_outro_em, atendida_em) < NOW() - INTERVAL " . CHAMADA_SUMIU_EM_ANDAMENTO . " SECOND) THEN 'encerrada'
                END AS novo_status
           FROM chamadas WHERE status IN ('tocando', 'em_andamento') $filtro"
    );

    foreach ($stmt->fetchAll() as $c) {
        if ($c['novo_status'] === null) {
            continue;
        }
        $mudou = $pdo->prepare('UPDATE chamadas SET status = ?, encerrada_em = NOW() WHERE id = ? AND status = ?');
        $mudou->execute([$c['novo_status'], $c['id'], $c['status']]);
        if ($mudou->rowCount() === 1) {
            avisar_fim_da_chamada($c, $c['novo_status'], 'a conexão foi perdida');
        }
    }
}

/** Escreve no chat o que aconteceu com a chamada. */
function avisar_fim_da_chamada(array $chamada, string $status, string $motivoEncerrada = ''): void
{
    $nome = nome_da_chamada((bool) $chamada['com_video']);
    $texto = match ($status) {
        'perdida'   => "$nome perdida: ninguém atendeu.",
        'cancelada' => "$nome cancelada.",
        'recusada'  => "$nome recusada.",
        'encerrada' => "$nome encerrada" . ($motivoEncerrada !== '' ? " ($motivoEncerrada)" : '') . '.',
        default     => '',
    };

    // Com duração, quando a chamada chegou a ser atendida.
    if ($status === 'encerrada' && !empty($chamada['atendida_em'])) {
        $stmt = db()->prepare('SELECT TIMESTAMPDIFF(SECOND, atendida_em, COALESCE(encerrada_em, NOW())) FROM chamadas WHERE id = ?');
        $stmt->execute([$chamada['id']]);
        $texto = "$nome encerrada · " . formatar_duracao(max(0, (int) $stmt->fetchColumn())) . ($motivoEncerrada !== '' ? " ($motivoEncerrada)" : '') . '.';
    }

    if ($texto !== '') {
        mensagem_do_sistema((int) $chamada['atendimento_id'], (int) $chamada['iniciador_id'], $texto);
    }
}

function chamada_por_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM chamadas WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/** A chamada em curso (tocando ou em andamento) do atendimento, se houver. */
function chamada_em_curso(int $atendimentoId): ?array
{
    $stmt = db()->prepare("SELECT * FROM chamadas WHERE atendimento_id = ? AND status IN ('tocando', 'em_andamento') ORDER BY id DESC LIMIT 1");
    $stmt->execute([$atendimentoId]);
    return $stmt->fetch() ?: null;
}

/** Formato enviado ao navegador, do ponto de vista de quem consulta. */
function chamada_para_json(array $c, int $euId): array
{
    return [
        'id'         => (int) $c['id'],
        'status'     => $c['status'],
        'video'      => (bool) $c['com_video'],
        'iniciador'  => (int) $c['iniciador_id'] === $euId ? 'eu' : 'outro',
        'atendida_em' => $c['atendida_em'] ? (new DateTime($c['atendida_em']))->format('Y-m-d\TH:i:s') : null,
    ];
}

/** O atendimento permite chamadas? (ativo e as duas contas ativas.) */
function atendimento_permite_chamada(array $atendimento): bool
{
    return $atendimento['status'] === 'ativo' && $atendimento['profissional_ativo'] && $atendimento['usuario_ativo'];
}

/**
 * Começa uma chamada. Devolve [chamada, null, 200] ou [null, mensagem, status HTTP].
 * Uma chamada por vez em cada atendimento (a trava na linha do atendimento evita duas ao mesmo tempo).
 */
function iniciar_chamada(array $atendimento, int $euId, bool $comVideo): array
{
    if (!atendimento_permite_chamada($atendimento)) {
        return [null, 'Este atendimento foi encerrado. Não é possível ligar.', 409];
    }

    $pdo = db();
    expirar_chamadas((int) $atendimento['id']);

    $pdo->beginTransaction();
    try {
        $pdo->prepare('SELECT id FROM atendimentos WHERE id = ? FOR UPDATE')->execute([$atendimento['id']]);
        if (chamada_em_curso((int) $atendimento['id'])) {
            $pdo->rollBack();
            return [null, 'Já existe uma chamada em andamento neste atendimento.', 409];
        }
        $pdo->prepare("INSERT INTO chamadas (atendimento_id, iniciador_id, com_video, visto_iniciador_em) VALUES (?, ?, ?, NOW())")
            ->execute([$atendimento['id'], $euId, $comVideo ? 1 : 0]);
        $id = (int) $pdo->lastInsertId();
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erro ao iniciar chamada: ' . $e->getMessage());
        return [null, 'Não foi possível ligar agora. Tente novamente em instantes.', 500];
    }

    return [chamada_para_json(chamada_por_id($id), $euId), null, 200];
}

/** Quem foi chamado atende. Só vale enquanto a chamada está tocando. */
function atender_chamada(array $chamada, int $euId): bool
{
    if ((int) $chamada['iniciador_id'] === $euId) {
        return false;   // quem ligou não atende a própria chamada
    }
    $stmt = db()->prepare("UPDATE chamadas SET status = 'em_andamento', atendida_em = NOW(), visto_outro_em = NOW() WHERE id = ? AND status = 'tocando'");
    $stmt->execute([$chamada['id']]);
    return $stmt->rowCount() === 1;
}

/**
 * Desligar, em qualquer momento:
 *   tocando + quem ligou -> cancelada;  tocando + quem foi chamado -> recusada;  em andamento -> encerrada.
 * Devolve o novo status ou null se a chamada já tinha acabado.
 */
function encerrar_chamada(array $chamada, int $euId): ?string
{
    if (!in_array($chamada['status'], ['tocando', 'em_andamento'], true)) {
        return null;   // já terminou: desligar de novo não muda nada
    }

    $novo = $chamada['status'] === 'em_andamento'
        ? 'encerrada'
        : ((int) $chamada['iniciador_id'] === $euId ? 'cancelada' : 'recusada');

    // O "AND status = ?" garante que só uma das duas pessoas (ou o servidor) fecha a chamada.
    $stmt = db()->prepare('UPDATE chamadas SET status = ?, encerrada_em = NOW() WHERE id = ? AND status = ?');
    $stmt->execute([$novo, $chamada['id'], $chamada['status']]);
    if ($stmt->rowCount() !== 1) {
        return null;
    }
    avisar_fim_da_chamada($chamada, $novo);
    return $novo;
}

/** Anota que este lado ainda está na chamada. */
function bater_ponto_da_chamada(array $chamada, int $euId): void
{
    $coluna = (int) $chamada['iniciador_id'] === $euId ? 'visto_iniciador_em' : 'visto_outro_em';
    db()->prepare("UPDATE chamadas SET $coluna = NOW() WHERE id = ? AND status IN ('tocando', 'em_andamento')")->execute([$chamada['id']]);
}

/**
 * Confere e guarda um sinal WebRTC. Só entram formatos conhecidos (o resto é descartado), e cada lado só
 * manda o que lhe cabe: a oferta é de quem ligou, a resposta é de quem atendeu, candidatos ICE são dos dois.
 * Devolve [null, null] se ok, ou [mensagem, status HTTP].
 */
function guardar_sinal(array $chamada, int $euId, string $tipo, string $dadosJson): array
{
    if (!in_array($chamada['status'], ['tocando', 'em_andamento'], true)) {
        return ['Esta chamada já terminou.', 409];
    }
    $souQuemLigou = (int) $chamada['iniciador_id'] === $euId;
    if (($tipo === 'offer' && !$souQuemLigou) || ($tipo === 'answer' && $souQuemLigou)) {
        return ['Este sinal não é seu.', 403];
    }
    if ($tipo === 'answer' && $chamada['status'] !== 'em_andamento') {
        return ['A chamada ainda não foi atendida.', 409];
    }
    if (strlen($dadosJson) > CHAMADA_SINAL_MAX_BYTES) {
        return ['Sinal grande demais.', 413];
    }

    $dados = json_decode($dadosJson, true);
    if (!is_array($dados)) {
        return ['Sinal inválido.', 422];
    }

    if ($tipo === 'offer' || $tipo === 'answer') {
        if (($dados['type'] ?? null) !== $tipo || !is_string($dados['sdp'] ?? null) || $dados['sdp'] === '') {
            return ['Sinal inválido.', 422];
        }
        $limpo = ['type' => $tipo, 'sdp' => $dados['sdp']];
    } else {
        $candidato = $dados['candidate'] ?? null;
        if (!is_string($candidato) || strlen($candidato) > 2048) {
            return ['Sinal inválido.', 422];
        }
        $limpo = [
            'candidate'     => $candidato,
            'sdpMid'        => isset($dados['sdpMid']) && is_string($dados['sdpMid']) ? substr($dados['sdpMid'], 0, 64) : null,
            'sdpMLineIndex' => isset($dados['sdpMLineIndex']) && is_int($dados['sdpMLineIndex']) ? $dados['sdpMLineIndex'] : null,
        ];
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM sinais_chamada WHERE chamada_id = ?');
    $stmt->execute([$chamada['id']]);
    if ((int) $stmt->fetchColumn() >= CHAMADA_SINAIS_MAX) {
        return ['Sinais demais nesta chamada.', 429];
    }

    $pdo->prepare('INSERT INTO sinais_chamada (chamada_id, de_id, tipo, dados) VALUES (?, ?, ?, ?)')
        ->execute([$chamada['id'], $euId, $tipo, json_encode($limpo, JSON_UNESCAPED_SLASHES)]);
    return [null, 200];
}

/** Sinais mandados PELO OUTRO lado com id maior que $depois. */
function sinais_do_outro(int $chamadaId, int $euId, int $depois): array
{
    $stmt = db()->prepare('SELECT id, tipo, dados FROM sinais_chamada WHERE chamada_id = ? AND de_id <> ? AND id > ? ORDER BY id LIMIT 200');
    $stmt->execute([$chamadaId, $euId, $depois]);
    return array_map(fn($s) => ['id' => (int) $s['id'], 'tipo' => $s['tipo'], 'dados' => json_decode($s['dados'], true)], $stmt->fetchAll());
}

/** Chamada tocando para esta pessoa em qualquer atendimento (para o aviso no menu), ou null. */
function chamada_entrando_para(int $pessoaId): ?array
{
    expirar_chamadas();
    $stmt = db()->prepare(
        "SELECT c.id, c.atendimento_id, c.com_video, u.nome AS de_nome
           FROM chamadas c
           JOIN atendimentos a ON a.id = c.atendimento_id AND a.status = 'ativo'
           JOIN usuarios u ON u.id = c.iniciador_id
          WHERE c.status = 'tocando' AND c.iniciador_id <> ? AND (a.profissional_id = ? OR a.usuario_id = ?)
          ORDER BY c.id DESC LIMIT 1"
    );
    $stmt->execute([$pessoaId, $pessoaId, $pessoaId]);
    $c = $stmt->fetch();
    return $c ? ['id' => (int) $c['id'], 'atendimento' => (int) $c['atendimento_id'], 'video' => (bool) $c['com_video'], 'de' => $c['de_nome']] : null;
}
