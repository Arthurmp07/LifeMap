<?php
// Chat do atendimento. Quem chama já conferiu, com atendimento_do_participante(), que a pessoa participa
// do atendimento; aqui só se cuida das mensagens.

const CHAT_TEXTO_MAX = 2000;
const CHAT_LIMITE_POR_MINUTO = 30;
const CHAT_PAGINA = 50;

/**
 * Deixa o texto pronto para guardar: quebras de linha normalizadas, sem caracteres de controle
 * (fora \n e \t), sem excesso de linhas em branco e sem espaços nas pontas.
 * Devolve null se o texto não for UTF-8 válido.
 */
function limpar_texto_da_mensagem(string $texto): ?string
{
    if (!mb_check_encoding($texto, 'UTF-8')) {
        return null;
    }
    $texto = str_replace(["\r\n", "\r"], "\n", $texto);
    $texto = preg_replace('/[^\P{C}\n\t]+/u', '', $texto) ?? '';
    $texto = preg_replace("/\n{3,}/", "\n\n", $texto) ?? '';
    return trim($texto);
}

/** Linha do banco -> formato do navegador. 'de' é 'eu', 'outro' ou 'sistema'. */
function mensagem_para_json(array $m, int $euId): array
{
    return [
        'id'     => (int) $m['id'],
        'de'     => $m['tipo'] === 'sistema' ? 'sistema' : ((int) $m['remetente_id'] === $euId ? 'eu' : 'outro'),
        'texto'  => $m['texto'],
        'quando' => (new DateTime($m['criado_em']))->format('Y-m-d\TH:i:s'),
    ];
}

/**
 * Mensagens do atendimento, da mais antiga para a mais nova.
 *   $depois > 0: as mensagens novas (id maior que $depois), até 200;
 *   $antes  > 0: a página anterior (até CHAT_PAGINA mensagens com id menor que $antes);
 *   os dois em 0: as CHAT_PAGINA mais recentes (abertura do chat).
 * Devolve [lista, tem_mais] (tem_mais = existem mensagens mais antigas que a primeira da lista).
 */
function listar_mensagens(int $atendimentoId, int $euId, int $depois = 0, int $antes = 0): array
{
    $pdo = db();

    if ($depois > 0) {
        $stmt = $pdo->prepare('SELECT id, remetente_id, tipo, texto, criado_em FROM mensagens WHERE atendimento_id = ? AND id > ? ORDER BY id LIMIT 200');
        $stmt->execute([$atendimentoId, $depois]);
        return [array_map(fn($m) => mensagem_para_json($m, $euId), $stmt->fetchAll()), false];
    }

    $condicao = $antes > 0 ? 'AND id < ' . (int) $antes : '';
    $stmt = $pdo->prepare(
        "SELECT id, remetente_id, tipo, texto, criado_em FROM mensagens
          WHERE atendimento_id = ? $condicao ORDER BY id DESC LIMIT " . (CHAT_PAGINA + 1)
    );
    $stmt->execute([$atendimentoId]);
    $linhas = $stmt->fetchAll();

    $temMais = count($linhas) > CHAT_PAGINA;
    $linhas = array_reverse(array_slice($linhas, 0, CHAT_PAGINA));
    return [array_map(fn($m) => mensagem_para_json($m, $euId), $linhas), $temMais];
}

/** Maior id de mensagem MINHA que a outra pessoa já leu (0 se nenhuma): base do aviso "Lida". */
function ultima_mensagem_lida_pelo_outro(int $atendimentoId, int $euId): int
{
    $stmt = db()->prepare(
        "SELECT COALESCE(MAX(id), 0) FROM mensagens
          WHERE atendimento_id = ? AND remetente_id = ? AND tipo = 'texto' AND lida_em IS NOT NULL"
    );
    $stmt->execute([$atendimentoId, $euId]);
    return (int) $stmt->fetchColumn();
}

/** Marca como lidas as mensagens que a outra pessoa mandou. Devolve quantas mudaram. */
function marcar_mensagens_lidas(int $atendimentoId, int $euId): int
{
    $stmt = db()->prepare(
        "UPDATE mensagens SET lida_em = NOW()
          WHERE atendimento_id = ? AND remetente_id <> ? AND tipo = 'texto' AND lida_em IS NULL"
    );
    $stmt->execute([$atendimentoId, $euId]);
    return $stmt->rowCount();
}

/**
 * Envia uma mensagem de texto. $atendimento vem de atendimento_do_participante().
 * Devolve [mensagem no formato do navegador, null, 200] ou [null, mensagem de erro, status HTTP].
 */
function enviar_mensagem(array $atendimento, int $euId, string $textoBruto): array
{
    if ($atendimento['status'] !== 'ativo' || !$atendimento['profissional_ativo'] || !$atendimento['usuario_ativo']) {
        return [null, 'Este atendimento foi encerrado. A conversa ficou só para leitura.', 409];
    }

    $texto = limpar_texto_da_mensagem($textoBruto);
    if ($texto === null) {
        return [null, 'A mensagem tem caracteres inválidos.', 422];
    }
    if ($texto === '') {
        return [null, 'Escreva uma mensagem.', 422];
    }
    if (mb_strlen($texto) > CHAT_TEXTO_MAX) {
        return [null, 'A mensagem passa de ' . CHAT_TEXTO_MAX . ' caracteres. Divida em duas.', 422];
    }

    $pdo = db();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mensagens WHERE remetente_id = ? AND tipo = 'texto' AND criado_em > NOW() - INTERVAL 60 SECOND");
    $stmt->execute([$euId]);
    if ((int) $stmt->fetchColumn() >= CHAT_LIMITE_POR_MINUTO) {
        return [null, 'Você está enviando mensagens rápido demais. Espere alguns segundos.', 429];
    }

    $pdo->prepare("INSERT INTO mensagens (atendimento_id, remetente_id, tipo, texto) VALUES (?, ?, 'texto', ?)")
        ->execute([(int) $atendimento['id'], $euId, $texto]);
    $id = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT id, remetente_id, tipo, texto, criado_em FROM mensagens WHERE id = ?');
    $stmt->execute([$id]);
    return [mensagem_para_json($stmt->fetch(), $euId), null, 200];
}

/** O outro lado do atendimento, do ponto de vista de quem está olhando: nome e descrição curta. */
function outro_lado_do_atendimento(array $atendimento, int $euId): array
{
    if ((int) $atendimento['profissional_id'] === $euId) {
        return ['nome' => $atendimento['usuario_nome'], 'detalhe' => 'Paciente', 'papel' => 'usuario'];
    }
    return ['nome' => $atendimento['profissional_nome'], 'detalhe' => $atendimento['especialidade'] . ' · ' . $atendimento['registro'], 'papel' => 'profissional'];
}
