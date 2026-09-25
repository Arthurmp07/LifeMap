<?php
// Regras da rotina (agenda) e do registro de humor.
// Categorias e níveis de humor ficam só aqui: o JavaScript recebe esses dados do PHP.

// 'padrao' preenche o formulário quando a pessoa escolhe a categoria (título e horários sugeridos).
const CATEGORIAS_EVENTO = [
    ['chave' => 'estudo',   'nome' => 'Estudo',   'padrao' => ['titulo' => 'Estudar',            'inicio' => '19:00', 'fim' => '21:00']],
    ['chave' => 'trabalho', 'nome' => 'Trabalho', 'padrao' => ['titulo' => 'Trabalho',           'inicio' => '09:00', 'fim' => '17:00']],
    ['chave' => 'treino',   'nome' => 'Treino',   'padrao' => ['titulo' => 'Treino na academia', 'inicio' => '18:00', 'fim' => '19:00']],
    ['chave' => 'refeicao', 'nome' => 'Refeição', 'padrao' => ['titulo' => 'Almoço',             'inicio' => '12:00', 'fim' => '13:00']],
    ['chave' => 'sono',     'nome' => 'Sono',     'padrao' => ['titulo' => 'Dormir',             'inicio' => '23:00', 'fim' => '07:00']],
    ['chave' => 'lazer',    'nome' => 'Lazer',    'padrao' => ['titulo' => 'Lazer',              'inicio' => '20:00', 'fim' => '22:00']],
    ['chave' => 'geral',    'nome' => 'Geral',    'padrao' => ['titulo' => '',                   'inicio' => '08:00', 'fim' => '09:00']],
];

// Do pior ao melhor: o nível (1 a 5) é o que fica no banco; o emoji é só apresentação.
const NIVEIS_HUMOR = [
    ['nivel' => 1, 'emoji' => '😢', 'nome' => 'Muito mal'],
    ['nivel' => 2, 'emoji' => '😕', 'nome' => 'Mal'],
    ['nivel' => 3, 'emoji' => '😐', 'nome' => 'Normal'],
    ['nivel' => 4, 'emoji' => '🙂', 'nome' => 'Bem'],
    ['nivel' => 5, 'emoji' => '😄', 'nome' => 'Muito bem'],
];

const REPETICOES_EVENTO = ['nenhuma', 'diaria', 'uteis', 'semanal'];

const EVENTO_TITULO_MAX = 120;
const EVENTO_OBSERVACAO_MAX = 255;
const HUMOR_NOTA_MAX = 200;
const REPETICAO_MAX_OCORRENCIAS = 60;

function categoria_evento_valida(string $chave): bool
{
    return in_array($chave, array_column(CATEGORIAS_EVENTO, 'chave'), true);
}

function nivel_humor_valido(int $nivel): bool
{
    return in_array($nivel, array_column(NIVEIS_HUMOR, 'nivel'), true);
}

/** Converte "AAAA-MM-DD" (estrito) em data à meia-noite; null se for inválida. */
function ler_data(string $texto): ?DateTimeImmutable
{
    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $texto);
    return ($data && $data->format('Y-m-d') === $texto) ? $data : null;
}

function hora_valida(string $texto): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $texto);
}

/**
 * Início e fim de um evento que começa em $data às $horaInicio e termina às $horaFim.
 * Se o fim for menor que o início, o evento termina no dia seguinte (ex.: sono das 23:00 às 07:00).
 * Devolve null quando início e fim são iguais.
 */
function intervalo_do_evento(DateTimeImmutable $data, string $horaInicio, string $horaFim): ?array
{
    if ($horaInicio === $horaFim) {
        return null;
    }

    $inicio = $data->setTime((int) substr($horaInicio, 0, 2), (int) substr($horaInicio, 3, 2));
    $fim = $data->setTime((int) substr($horaFim, 0, 2), (int) substr($horaFim, 3, 2));
    if ($fim <= $inicio) {
        $fim = $fim->modify('+1 day');
    }

    return [$inicio, $fim];
}

/**
 * Datas de uma repetição, da data inicial até $ate (inclusive).
 * 'uteis' pula sábados e domingos (a própria data inicial também, se cair no fim de semana).
 */
function datas_da_repeticao(DateTimeImmutable $primeira, string $tipo, DateTimeImmutable $ate): array
{
    if ($tipo === 'nenhuma') {
        return [$primeira];
    }

    $passo = $tipo === 'semanal' ? '+7 days' : '+1 day';
    $datas = [];
    for ($d = $primeira; $d <= $ate && count($datas) <= REPETICAO_MAX_OCORRENCIAS; $d = $d->modify($passo)) {
        if ($tipo === 'uteis' && (int) $d->format('N') >= 6) {
            continue;
        }
        $datas[] = $d;
    }
    return $datas;
}

/** Eventos do usuário que se sobrepõem ao período (fim de um = início do outro não conta). */
function eventos_em_conflito(int $usuarioId, DateTimeImmutable $inicio, DateTimeImmutable $fim, int $ignorarId = 0): array
{
    $stmt = db()->prepare(
        'SELECT id, titulo, categoria, inicio, fim FROM eventos
          WHERE usuario_id = ? AND id <> ? AND inicio < ? AND fim > ?
          ORDER BY inicio LIMIT 3'
    );
    $stmt->execute([$usuarioId, $ignorarId, $fim->format('Y-m-d H:i:s'), $inicio->format('Y-m-d H:i:s')]);
    return $stmt->fetchAll();
}

/** Linha do banco -> formato enviado ao navegador (datas locais, sem fuso). */
function evento_para_json(array $linha): array
{
    return [
        'id'         => (int) $linha['id'],
        'titulo'     => $linha['titulo'],
        'categoria'  => $linha['categoria'],
        'inicio'     => (new DateTime($linha['inicio']))->format('Y-m-d\TH:i:s'),
        'fim'        => (new DateTime($linha['fim']))->format('Y-m-d\TH:i:s'),
        'observacao' => $linha['observacao'] ?? '',
        'serie'      => $linha['serie_id'],
    ];
}
