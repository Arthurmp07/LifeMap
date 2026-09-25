<?php
// Contas de profissional: cadastro, ativação e senha provisória (tudo feito só pelo administrador).

const ESPECIALIDADES_SUGERIDAS = ['Nutricionista', 'Educador físico', 'Personal trainer', 'Psicólogo(a)', 'Médico(a)', 'Fisioterapeuta'];

/** Profissionais com a quantidade de atendimentos em andamento, do mais recente ao mais antigo. */
function listar_profissionais(): array
{
    $stmt = db()->query(
        "SELECT u.id, u.nome, u.email, u.telefone, u.ativo, u.trocar_senha, p.especialidade, p.registro, p.criado_em,
                (SELECT COUNT(*) FROM atendimentos a WHERE a.profissional_id = u.id AND a.status = 'ativo') AS pacientes
           FROM usuarios u
           JOIN profissionais p ON p.usuario_id = u.id
          WHERE u.papel = 'profissional'
          ORDER BY u.ativo DESC, u.nome"
    );
    return $stmt->fetchAll();
}

/**
 * Valida os dados do formulário de cadastro de profissional.
 * Devolve [dados limpos, lista de erros].
 */
function validar_novo_profissional(array $post): array
{
    $dados = [
        'nome'          => trim((string) ($post['nome'] ?? '')),
        'email'         => trim((string) ($post['email'] ?? '')),
        'telefone'      => trim((string) ($post['telefone'] ?? '')),
        'especialidade' => trim((string) ($post['especialidade'] ?? '')),
        'registro'      => trim((string) ($post['registro'] ?? '')),
    ];
    $erros = [];

    if (mb_strlen($dados['nome']) < 3 || mb_strlen($dados['nome']) > 100) {
        $erros[] = 'Informe o nome completo do profissional.';
    }
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($dados['email']) > 100) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if (!telefone_valido($dados['telefone'])) {
        $erros[] = 'Informe um telefone com DDD.';
    }
    if (mb_strlen($dados['especialidade']) < 3 || mb_strlen($dados['especialidade']) > 80) {
        $erros[] = 'Informe a especialidade (de 3 a 80 caracteres).';
    }
    if (mb_strlen($dados['registro']) < 3 || mb_strlen($dados['registro']) > 40) {
        $erros[] = 'Informe o registro profissional (ex.: CRN-3 12345).';
    }

    return [$dados, $erros];
}

/**
 * Cria a conta do profissional com uma senha provisória.
 * Devolve [senha provisória, null] ou [null, mensagem de erro].
 */
function cadastrar_profissional(array $dados): array
{
    $senha = senha_provisoria();
    $pdo = db();

    try {
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO usuarios (nome, email, telefone, senha, papel, trocar_senha) VALUES (?, ?, ?, ?, 'profissional', 1)")
            ->execute([$dados['nome'], $dados['email'], $dados['telefone'], password_hash($senha, PASSWORD_BCRYPT)]);
        $pdo->prepare('INSERT INTO profissionais (usuario_id, especialidade, registro) VALUES (?, ?, ?)')
            ->execute([(int) $pdo->lastInsertId(), $dados['especialidade'], $dados['registro']]);
        $pdo->commit();
        return [$senha, null];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (($e->errorInfo[1] ?? null) === 1062) {
            return [null, 'Já existe uma conta com este e-mail.'];
        }
        error_log('Erro ao cadastrar profissional: ' . $e->getMessage());
        return [null, 'Não foi possível cadastrar agora. Tente novamente em instantes.'];
    }
}

/** Confere que o id é de um profissional (e não de um usuário ou administrador). */
function eh_profissional(int $id): bool
{
    $stmt = db()->prepare("SELECT 1 FROM usuarios WHERE id = ? AND papel = 'profissional'");
    $stmt->execute([$id]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Desativa ou reativa um profissional. Ao desativar, os atendimentos em andamento são encerrados,
 * os convites pendentes somem e as chamadas em curso terminam (a conversa antiga continua guardada).
 */
function definir_profissional_ativo(int $id, bool $ativo, int $porId): bool
{
    if (!eh_profissional($id)) {
        return false;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE usuarios SET ativo = ? WHERE id = ?')->execute([$ativo ? 1 : 0, $id]);

        if (!$ativo) {
            $pdo->prepare(
                "UPDATE chamadas c JOIN atendimentos a ON a.id = c.atendimento_id
                    SET c.status = IF(c.status = 'tocando', 'cancelada', 'encerrada'), c.encerrada_em = NOW()
                  WHERE a.profissional_id = ? AND c.status IN ('tocando', 'em_andamento')"
            )->execute([$id]);
            $pdo->prepare("UPDATE atendimentos SET status = 'encerrado', encerrado_em = NOW(), encerrado_por = ? WHERE profissional_id = ? AND status = 'ativo'")
                ->execute([$porId, $id]);
            $pdo->prepare("DELETE FROM atendimentos WHERE profissional_id = ? AND status = 'pendente'")->execute([$id]);
        }
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erro ao alterar o profissional: ' . $e->getMessage());
        return false;
    }
}

/** Gera outra senha provisória (para quem esqueceu a senha). Devolve a senha ou null. */
function redefinir_senha_do_profissional(int $id): ?string
{
    if (!eh_profissional($id)) {
        return null;
    }
    $senha = senha_provisoria();
    db()->prepare('UPDATE usuarios SET senha = ?, trocar_senha = 1 WHERE id = ?')
        ->execute([password_hash($senha, PASSWORD_BCRYPT), $id]);
    return $senha;
}
