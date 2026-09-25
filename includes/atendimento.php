<?php
// Atendimento: o vínculo entre um profissional e um usuário.
//
//   convite (profissional) -> aceite (usuário) -> atendimento ativo -> encerrado (por qualquer um dos dois)
//                          \-> recusado
//
// Há um registro por par (profissional, usuário) em `atendimentos`; convidar de novo reabre o mesmo
// registro, e o histórico do chat fica guardado nele. O profissional só enxerga os dados de saúde do
// usuário enquanto o atendimento está ATIVO (ver ficha_liberada_para()).

const CONVITES_PENDENTES_MAX = 30;   // por profissional (evita disparar convites em massa)
const CONVITE_ESPERA_DIAS = 7;       // depois de uma recusa (ou de o usuário encerrar), espera antes de convidar de novo
const BUSCA_MIN_CARACTERES = 3;
const BUSCA_MAX_RESULTADOS = 10;

/** Protege os curingas do LIKE (%, _ e \) para que o texto digitado seja buscado literalmente. */
function like_literal(string $texto): string
{
    return addcslashes($texto, '%_\\');
}

/** "maria@exemplo.com" -> "m***@exemplo.com" (o profissional reconhece a pessoa sem ver o e-mail inteiro). */
function mascarar_email(string $email): string
{
    $partes = explode('@', $email, 2);
    if (count($partes) !== 2 || $partes[0] === '') {
        return '***';
    }
    return mb_substr($partes[0], 0, 1) . '***@' . $partes[1];
}

// ---------- busca e convite ----------

/**
 * Usuários (perfil "usuario", ativos) cujo nome ou e-mail contém o texto, com a situação do atendimento
 * deles com este profissional. Só devolve o mínimo para reconhecer a pessoa: nada de dados de saúde.
 */
function buscar_usuarios_para_convite(int $profissionalId, string $termo): array
{
    $termo = trim($termo);
    if (mb_strlen($termo) < BUSCA_MIN_CARACTERES) {
        return [];
    }

    $like = '%' . like_literal($termo) . '%';
    $stmt = db()->prepare(
        "SELECT u.id, u.nome, u.email, a.status
           FROM usuarios u
           LEFT JOIN atendimentos a ON a.usuario_id = u.id AND a.profissional_id = ?
          WHERE u.papel = 'usuario' AND u.ativo = 1 AND (u.nome LIKE ? OR u.email LIKE ?)
          ORDER BY u.nome
          LIMIT " . BUSCA_MAX_RESULTADOS
    );
    $stmt->execute([$profissionalId, $like, $like]);

    return array_map(fn($u) => [
        'id'     => (int) $u['id'],
        'nome'   => $u['nome'],
        'email'  => mascarar_email($u['email']),
        'status' => $u['status'],   // null = nunca houve convite
    ], $stmt->fetchAll());
}

/**
 * Envia (ou reenvia) um convite de atendimento. Devolve [deu certo, mensagem].
 * O profissional precisa estar ativo (quem chama já conferiu o perfil com exigir_papel).
 */
function convidar_usuario(int $profissionalId, int $usuarioId): array
{
    $pdo = db();

    $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ? AND papel = 'usuario' AND ativo = 1");
    $stmt->execute([$usuarioId]);
    $nome = $stmt->fetchColumn();
    if ($nome === false) {
        return [false, 'Usuário não encontrado.'];
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM atendimentos WHERE profissional_id = ? AND status = ?');
    $stmt->execute([$profissionalId, 'pendente']);
    if ((int) $stmt->fetchColumn() >= CONVITES_PENDENTES_MAX) {
        return [false, 'Você já tem ' . CONVITES_PENDENTES_MAX . ' convites aguardando resposta. Espere as respostas antes de convidar mais pessoas.'];
    }

    // Situação atual do par. A espera depois de uma recusa (ou de o usuário encerrar) é calculada no
    // banco, com o mesmo relógio dos registros.
    $stmt = $pdo->prepare(
        "SELECT id, status, liberado_em, (liberado_em IS NOT NULL AND liberado_em > NOW()) AS bloqueado
           FROM (SELECT id, status,
                        DATE_ADD(CASE WHEN status = 'recusado' THEN respondido_em
                                      WHEN status = 'encerrado' AND encerrado_por = usuario_id THEN encerrado_em END,
                                 INTERVAL " . CONVITE_ESPERA_DIAS . " DAY) AS liberado_em
                   FROM atendimentos WHERE profissional_id = ? AND usuario_id = ?) par"
    );
    $stmt->execute([$profissionalId, $usuarioId]);
    $atual = $stmt->fetch();

    try {
        if (!$atual) {
            $pdo->prepare("INSERT INTO atendimentos (profissional_id, usuario_id, status) VALUES (?, ?, 'pendente')")
                ->execute([$profissionalId, $usuarioId]);
            return [true, "Convite enviado para $nome. Quando a pessoa aceitar, o atendimento começa."];
        }

        if ($atual['status'] === 'pendente') {
            return [false, 'Você já enviou um convite a esta pessoa. Aguarde a resposta.'];
        }
        if ($atual['status'] === 'ativo') {
            return [false, 'Esta pessoa já está em atendimento com você.'];
        }

        if ($atual['bloqueado']) {
            $data = (new DateTime($atual['liberado_em']))->format('d/m/Y');
            return [false, "Esta pessoa não quis o atendimento há pouco tempo. Você poderá convidar de novo a partir de $data."];
        }

        // Recusado ou encerrado (e fora da espera): reabre o mesmo registro, mantendo o histórico do chat.
        $pdo->prepare(
            "UPDATE atendimentos
                SET status = 'pendente', convidado_em = NOW(), respondido_em = NULL, encerrado_em = NULL,
                    encerrado_por = NULL, ficha_vista_em = NULL
              WHERE id = ? AND status IN ('recusado', 'encerrado')"
        )->execute([$atual['id']]);
        return [true, "Convite enviado para $nome. Quando a pessoa aceitar, o atendimento começa."];
    } catch (PDOException $e) {
        error_log('Erro ao convidar: ' . $e->getMessage());
        return [false, 'Não foi possível enviar o convite agora. Tente novamente em instantes.'];
    }
}

/** Retira um convite ainda sem resposta (só o profissional que enviou). */
function cancelar_convite(int $atendimentoId, int $profissionalId): bool
{
    $stmt = db()->prepare("DELETE FROM atendimentos WHERE id = ? AND profissional_id = ? AND status = 'pendente'");
    $stmt->execute([$atendimentoId, $profissionalId]);
    return $stmt->rowCount() > 0;
}

/** O usuário aceita ou recusa um convite feito a ele. O profissional ainda precisa estar ativo. */
function responder_convite(int $atendimentoId, int $usuarioId, bool $aceitar): bool
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "UPDATE atendimentos a
               JOIN usuarios p ON p.id = a.profissional_id AND p.ativo = 1 AND p.papel = 'profissional'
                SET a.status = ?, a.respondido_em = NOW()
              WHERE a.id = ? AND a.usuario_id = ? AND a.status = 'pendente'"
        );
        $stmt->execute([$aceitar ? 'ativo' : 'recusado', $atendimentoId, $usuarioId]);
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            return false;
        }
        if ($aceitar) {
            mensagem_do_sistema($atendimentoId, $usuarioId, 'Atendimento iniciado. Vocês já podem conversar por aqui.');
        }
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erro ao responder convite: ' . $e->getMessage());
        return false;
    }
}

/**
 * Encerra um atendimento ativo. Qualquer um dos dois pode encerrar; o profissional deixa de ver os
 * dados do usuário na hora. A conversa fica guardada, só para leitura. Termina também chamadas em curso.
 */
function encerrar_atendimento(int $atendimentoId, int $pessoaId): bool
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "UPDATE atendimentos SET status = 'encerrado', encerrado_em = NOW(), encerrado_por = ?
              WHERE id = ? AND status = 'ativo' AND (profissional_id = ? OR usuario_id = ?)"
        );
        $stmt->execute([$pessoaId, $atendimentoId, $pessoaId, $pessoaId]);
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            return false;
        }

        $pdo->prepare(
            "UPDATE chamadas SET status = IF(status = 'tocando', 'cancelada', 'encerrada'), encerrada_em = NOW()
              WHERE atendimento_id = ? AND status IN ('tocando', 'em_andamento')"
        )->execute([$atendimentoId]);

        $stmt = $pdo->prepare('SELECT nome FROM usuarios WHERE id = ?');
        $stmt->execute([$pessoaId]);
        mensagem_do_sistema($atendimentoId, $pessoaId, 'Atendimento encerrado por ' . $stmt->fetchColumn() . '.');

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erro ao encerrar atendimento: ' . $e->getMessage());
        return false;
    }
}

/** Aviso automático dentro da conversa (não conta como mensagem não lida). */
function mensagem_do_sistema(int $atendimentoId, int $remetenteId, string $texto): void
{
    db()->prepare("INSERT INTO mensagens (atendimento_id, remetente_id, tipo, texto, lida_em) VALUES (?, ?, 'sistema', ?, NOW())")
        ->execute([$atendimentoId, $remetenteId, mb_substr($texto, 0, 2000)]);
}

// ---------- consultas de acesso ----------

/**
 * Atendimento com os dados dos dois lados, se a pessoa participa dele (senão null).
 * É a porta de entrada do chat e da chamada: quem não participa não recebe nada.
 */
function atendimento_do_participante(int $atendimentoId, int $pessoaId): ?array
{
    $stmt = db()->prepare(
        "SELECT a.*, up.nome AS profissional_nome, up.ativo AS profissional_ativo, pr.especialidade, pr.registro,
                uu.nome AS usuario_nome, uu.ativo AS usuario_ativo
           FROM atendimentos a
           JOIN usuarios up ON up.id = a.profissional_id
           JOIN profissionais pr ON pr.usuario_id = a.profissional_id
           JOIN usuarios uu ON uu.id = a.usuario_id
          WHERE a.id = ? AND (a.profissional_id = ? OR a.usuario_id = ?)"
    );
    $stmt->execute([$atendimentoId, $pessoaId, $pessoaId]);
    return $stmt->fetch() ?: null;
}

/**
 * O profissional pode ver os dados de saúde deste usuário? Só com atendimento ATIVO e as duas contas ativas.
 * Devolve o atendimento (ou null). Toda tela ou arquivo com dados do usuário passa por aqui.
 */
function ficha_liberada_para(int $profissionalId, int $usuarioId): ?array
{
    $stmt = db()->prepare(
        "SELECT a.*
           FROM atendimentos a
           JOIN usuarios p ON p.id = a.profissional_id AND p.ativo = 1 AND p.papel = 'profissional'
           JOIN usuarios u ON u.id = a.usuario_id AND u.ativo = 1 AND u.papel = 'usuario'
          WHERE a.profissional_id = ? AND a.usuario_id = ? AND a.status = 'ativo'"
    );
    $stmt->execute([$profissionalId, $usuarioId]);
    return $stmt->fetch() ?: null;
}

/** Registra que o profissional abriu a ficha (o usuário vê essa data na página de atendimento). */
function registrar_visita_da_ficha(int $atendimentoId): void
{
    db()->prepare('UPDATE atendimentos SET ficha_vista_em = NOW() WHERE id = ?')->execute([$atendimentoId]);
}

// ---------- listas ----------

const SQL_NAO_LIDAS = "(SELECT COUNT(*) FROM mensagens m
                         WHERE m.atendimento_id = a.id AND m.tipo = 'texto' AND m.remetente_id <> ? AND m.lida_em IS NULL)";

/** Atendimentos do profissional (em andamento, convites, encerrados), com mensagens não lidas. */
function atendimentos_do_profissional(int $profissionalId): array
{
    $stmt = db()->prepare(
        "SELECT a.id, a.status, a.usuario_id, a.convidado_em, a.respondido_em, a.encerrado_em, a.ficha_vista_em,
                u.nome AS usuario_nome, " . SQL_NAO_LIDAS . " AS nao_lidas
           FROM atendimentos a
           JOIN usuarios u ON u.id = a.usuario_id
          WHERE a.profissional_id = ?
          ORDER BY FIELD(a.status, 'ativo', 'pendente', 'encerrado', 'recusado'), u.nome"
    );
    $stmt->execute([$profissionalId, $profissionalId]);
    return $stmt->fetchAll();
}

/** Atendimentos do usuário: convites recebidos, em andamento e encerrados (recusados não aparecem). */
function atendimentos_do_usuario(int $usuarioId): array
{
    $stmt = db()->prepare(
        "SELECT a.id, a.status, a.profissional_id, a.convidado_em, a.respondido_em, a.encerrado_em, a.ficha_vista_em,
                up.nome AS profissional_nome, pr.especialidade, pr.registro, " . SQL_NAO_LIDAS . " AS nao_lidas
           FROM atendimentos a
           JOIN usuarios up ON up.id = a.profissional_id AND up.ativo = 1
           JOIN profissionais pr ON pr.usuario_id = a.profissional_id
          WHERE a.usuario_id = ? AND a.status IN ('pendente', 'ativo', 'encerrado')
          ORDER BY FIELD(a.status, 'pendente', 'ativo', 'encerrado'), up.nome"
    );
    $stmt->execute([$usuarioId, $usuarioId]);
    return $stmt->fetchAll();
}

/**
 * Números do menu: convites a responder (usuário) e mensagens não lidas em atendimentos ativos.
 * ['convites' => n, 'nao_lidas' => n, 'total' => n]
 */
function resumo_de_avisos(int $pessoaId, string $papel): array
{
    $pdo = db();
    $convites = 0;
    if ($papel === 'usuario') {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM atendimentos a JOIN usuarios p ON p.id = a.profissional_id AND p.ativo = 1
              WHERE a.usuario_id = ? AND a.status = 'pendente'"
        );
        $stmt->execute([$pessoaId]);
        $convites = (int) $stmt->fetchColumn();
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM mensagens m JOIN atendimentos a ON a.id = m.atendimento_id
          WHERE (a.profissional_id = ? OR a.usuario_id = ?) AND a.status = 'ativo'
            AND m.tipo = 'texto' AND m.remetente_id <> ? AND m.lida_em IS NULL"
    );
    $stmt->execute([$pessoaId, $pessoaId, $pessoaId]);
    $naoLidas = (int) $stmt->fetchColumn();

    return ['convites' => $convites, 'nao_lidas' => $naoLidas, 'total' => $convites + $naoLidas];
}
