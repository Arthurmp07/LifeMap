<?php
// Ficha do paciente: o que o profissional vê de um usuário com atendimento ativo.
// Estas funções só leem; quem chama precisa ter conferido ficha_liberada_para() antes.

const FICHA_DIAS_HUMOR = 30;

/**
 * Humor dos últimos $dias dias (hoje incluído), do mais antigo ao mais novo:
 * [['data' => 'AAAA-MM-DD', 'nivel' => 1..5|null, 'nota' => string], ...]
 */
function humor_recente(int $usuarioId, int $dias = FICHA_DIAS_HUMOR): array
{
    $hoje = new DateTimeImmutable('today');
    $inicio = $hoje->modify('-' . ($dias - 1) . ' days');

    $stmt = db()->prepare('SELECT data, nivel, nota FROM humor WHERE usuario_id = ? AND data BETWEEN ? AND ?');
    $stmt->execute([$usuarioId, $inicio->format('Y-m-d'), $hoje->format('Y-m-d')]);
    $registros = [];
    foreach ($stmt->fetchAll() as $linha) {
        $registros[$linha['data']] = $linha;
    }

    $lista = [];
    for ($i = 0; $i < $dias; $i++) {
        $data = $inicio->modify("+$i days")->format('Y-m-d');
        $lista[] = [
            'data'  => $data,
            'nivel' => isset($registros[$data]) ? (int) $registros[$data]['nivel'] : null,
            'nota'  => (string) ($registros[$data]['nota'] ?? ''),
        ];
    }
    return $lista;
}

/** Emoji e nome de um nível de humor (1 a 5). */
function humor_do_nivel(int $nivel): ?array
{
    foreach (NIVEIS_HUMOR as $n) {
        if ($n['nivel'] === $nivel) {
            return $n;
        }
    }
    return null;
}

/**
 * Tempo da semana atual (segunda a domingo) por categoria da rotina, em minutos.
 * Eventos que atravessam o começo ou o fim da semana contam só a parte de dentro.
 * Devolve ['inicio' => data, 'fim' => data, 'eventos' => n, 'categorias' => [['chave','nome','minutos'], ...]]
 * com todas as categorias, na ordem de CATEGORIAS_EVENTO.
 */
function resumo_da_semana(int $usuarioId): array
{
    $hoje = new DateTimeImmutable('today');
    $inicio = $hoje->modify('monday this week');
    $fim = $inicio->modify('+7 days');

    $stmt = db()->prepare('SELECT categoria, inicio, fim FROM eventos WHERE usuario_id = ? AND inicio < ? AND fim > ?');
    $stmt->execute([$usuarioId, $fim->format('Y-m-d H:i:s'), $inicio->format('Y-m-d H:i:s')]);

    $minutos = array_fill_keys(array_column(CATEGORIAS_EVENTO, 'chave'), 0);
    $eventos = 0;
    foreach ($stmt->fetchAll() as $evento) {
        $de = max(new DateTimeImmutable($evento['inicio']), $inicio);
        $ate = min(new DateTimeImmutable($evento['fim']), $fim);
        $duracao = intdiv($ate->getTimestamp() - $de->getTimestamp(), 60);
        if ($duracao > 0 && isset($minutos[$evento['categoria']])) {
            $minutos[$evento['categoria']] += $duracao;
            $eventos++;
        }
    }

    $categorias = [];
    foreach (CATEGORIAS_EVENTO as $categoria) {
        $categorias[] = ['chave' => $categoria['chave'], 'nome' => $categoria['nome'], 'minutos' => $minutos[$categoria['chave']]];
    }

    return ['inicio' => $inicio, 'fim' => $fim->modify('-1 day'), 'eventos' => $eventos, 'categorias' => $categorias];
}

/** 150 -> "2 h 30 min"; 60 -> "1 h"; 45 -> "45 min"; 0 -> "—". */
function formatar_minutos(int $minutos): string
{
    if ($minutos <= 0) {
        return '—';
    }
    $h = intdiv($minutos, 60);
    $m = $minutos % 60;
    return ($h ? "$h h" : '') . ($h && $m ? ' ' : '') . ($m ? "$m min" : '');
}

/** Dados do usuário para a ficha (sem a senha nem nada de acesso). */
function dados_do_paciente(int $usuarioId): ?array
{
    $stmt = db()->prepare("SELECT " . COLUNAS_USUARIO . " FROM usuarios WHERE id = ? AND papel = 'usuario' AND ativo = 1");
    $stmt->execute([$usuarioId]);
    return $stmt->fetch() ?: null;
}
