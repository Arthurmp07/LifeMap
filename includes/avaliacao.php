<?php
// Avaliação física por foto: interpretação das medidas, comparação entre avaliações
// e acesso às fotos guardadas em storage/avaliacoes/ (fora do acesso público).
//
// O navegador (MediaPipe) só MEDE; todo texto e toda classificação saem daqui,
// para haver uma única fonte de verdade.
//
// Convenção dos sinais (lados da PESSOA avaliada, e não da imagem):
//   ombros / quadril: graus de inclinação, positivo = lado direito mais alto
//   cabeça:           % da largura dos ombros, positivo = deslocada para a direita
//   tronco:           graus de inclinação, positivo = inclinado para a direita

const AVALIACAO_LIMITES = [
    'razao'   => ['baixa' => 1.40, 'alta' => 1.80],   // ombros / quadril (entre articulações)
    'ombros'  => ['leve' => 2.0, 'acentuado' => 5.0],
    'quadril' => ['leve' => 2.0, 'acentuado' => 5.0],
    'cabeca'  => ['leve' => 10.0, 'acentuado' => 20.0],
    'tronco'  => ['leve' => 2.0, 'acentuado' => 5.0],
];

// Faixas aceitas ao receber medidas do navegador (fora disso, a medição é considerada inválida).
const AVALIACAO_FAIXAS_VALIDAS = [
    'razao'   => [0.3, 4.0],
    'ombros'  => [-60.0, 60.0],
    'quadril' => [-60.0, 60.0],
    'cabeca'  => [-150.0, 150.0],
    'tronco'  => [-60.0, 60.0],
];

const AVALIACAO_MAX_POR_USUARIO = 60;
const AVALIACAO_FOTO_MAX_BYTES = 3 * 1024 * 1024;

const AVALIACAO_AVISO = 'Estimativa visual automática feita a partir de uma única foto. Ela não mede peso nem gordura corporal e não substitui a avaliação de um profissional.';

/** Lê as medidas enviadas pelo navegador. Devolve null se faltar algo ou estiver fora do razoável. */
function ler_medidas_avaliacao(array $origem): ?array
{
    $medidas = [];
    foreach (AVALIACAO_FAIXAS_VALIDAS as $chave => [$minimo, $maximo]) {
        $valor = ler_decimal($origem[$chave] ?? '');
        if ($valor === null || $valor < $minimo || $valor > $maximo) {
            return null;
        }
        $medidas[$chave] = round($valor, 3);
    }
    $medidas['corpo_inteiro'] = ($origem['corpo_inteiro'] ?? '') === '1';
    return $medidas;
}

/** Classifica um desvio em 'ok', 'leve' ou 'acentuado' pelo valor absoluto. */
function estado_do_desvio(float $valor, string $chave): string
{
    $limites = AVALIACAO_LIMITES[$chave];
    $absoluto = abs($valor);
    if ($absoluto < $limites['leve']) {
        return 'ok';
    }
    return $absoluto < $limites['acentuado'] ? 'leve' : 'acentuado';
}

/** Interpreta as medidas: itens com valor, estado e texto, além de um resumo. */
function interpretar_avaliacao(array $m): array
{
    $rotulos = ['ok' => 'Alinhado', 'leve' => 'Leve desvio', 'acentuado' => 'Desvio acentuado', 'info' => 'Referência'];
    $lado = fn(float $v): string => $v > 0 ? 'direito' : 'esquerdo';
    $graus = fn(float $v): string => formatar_decimal(abs($v), 1) . '°';
    $itens = [];

    // Proporção (só descreve o formato: não é um diagnóstico)
    $razao = (float) $m['razao'];
    $texto = 'Ombros e quadril em proporção equilibrada.';
    if ($razao < AVALIACAO_LIMITES['razao']['baixa']) {
        $texto = 'Quadril proporcionalmente mais largo que os ombros.';
    } elseif ($razao > AVALIACAO_LIMITES['razao']['alta']) {
        $texto = 'Ombros proporcionalmente mais largos que o quadril (formato em V).';
    }
    $itens[] = ['chave' => 'razao', 'titulo' => 'Proporção ombros/quadril', 'curto' => 'proporção',
        'valor' => formatar_decimal($razao, 2), 'estado' => 'info', 'texto' => $texto];

    // Ombros
    $estado = estado_do_desvio((float) $m['ombros'], 'ombros');
    $itens[] = ['chave' => 'ombros', 'titulo' => 'Nivelamento dos ombros', 'curto' => 'ombros', 'valor' => $graus((float) $m['ombros']),
        'estado' => $estado, 'texto' => match ($estado) {
            'ok'    => 'Ombros nivelados.',
            'leve'  => 'Leve desnível: ombro ' . $lado((float) $m['ombros']) . ' um pouco mais alto.',
            default => 'Desnível acentuado: ombro ' . $lado((float) $m['ombros']) . ' mais alto.',
        }];

    // Quadril
    $estado = estado_do_desvio((float) $m['quadril'], 'quadril');
    $itens[] = ['chave' => 'quadril', 'titulo' => 'Nivelamento do quadril', 'curto' => 'quadril', 'valor' => $graus((float) $m['quadril']),
        'estado' => $estado, 'texto' => match ($estado) {
            'ok'    => 'Quadril nivelado.',
            'leve'  => 'Leve desnível: lado ' . $lado((float) $m['quadril']) . ' um pouco mais alto.',
            default => 'Desnível acentuado: lado ' . $lado((float) $m['quadril']) . ' mais alto.',
        }];

    // Cabeça
    $estado = estado_do_desvio((float) $m['cabeca'], 'cabeca');
    $itens[] = ['chave' => 'cabeca', 'titulo' => 'Alinhamento da cabeça', 'curto' => 'cabeça',
        'valor' => formatar_decimal(abs((float) $m['cabeca']), 0) . '%', 'estado' => $estado,
        'texto' => $estado === 'ok'
            ? 'Cabeça centralizada sobre os ombros.'
            : 'Cabeça deslocada para a ' . ((float) $m['cabeca'] > 0 ? 'direita' : 'esquerda') . '.'];

    // Tronco
    $estado = estado_do_desvio((float) $m['tronco'], 'tronco');
    $itens[] = ['chave' => 'tronco', 'titulo' => 'Alinhamento do tronco', 'curto' => 'tronco', 'valor' => $graus((float) $m['tronco']),
        'estado' => $estado,
        'texto' => $estado === 'ok'
            ? 'Tronco alinhado na vertical.'
            : 'Tronco inclinado para a ' . ((float) $m['tronco'] > 0 ? 'direita' : 'esquerda') . '.'];

    foreach ($itens as &$item) {
        $item['rotulo'] = $rotulos[$item['estado']];
    }
    unset($item);

    $atencao = array_values(array_filter($itens, fn($i) => in_array($i['estado'], ['leve', 'acentuado'], true)));
    $nomes = implode(', ', array_column($atencao, 'curto'));
    if (!$atencao) {
        $resumo = 'Nesta foto, ombros, quadril, cabeça e tronco aparecem bem alinhados.';
    } else {
        $resumo = 'Pontos de atenção nesta foto: ' . $nomes . '.';
        if (in_array('acentuado', array_column($atencao, 'estado'), true)) {
            $resumo .= ' Se o desvio se repetir em outras fotos, vale conversar com um fisioterapeuta ou educador físico.';
        }
    }
    if (empty($m['corpo_inteiro'])) {
        $resumo .= ' As pernas ficaram fora do enquadramento: para comparar ao longo do tempo, use sempre o mesmo enquadramento.';
    }

    return ['itens' => $itens, 'resumo' => $resumo, 'aviso' => AVALIACAO_AVISO, 'atencao' => array_column($atencao, 'chave')];
}

/** Compara duas avaliações (a atual com a anterior), medida por medida. */
function comparar_avaliacoes(array $atual, array $anterior): array
{
    $sinal = fn(float $v, int $casas, string $sufixo): string => ($v > 0 ? '+' : ($v < 0 ? '-' : '')) . formatar_decimal(abs($v), $casas) . $sufixo;
    $linhas = [];

    $dif = (float) $atual['razao'] - (float) $anterior['razao'];
    $linhas[] = ['titulo' => 'Proporção ombros/quadril', 'anterior' => formatar_decimal((float) $anterior['razao'], 2),
        'atual' => formatar_decimal((float) $atual['razao'], 2), 'variacao' => $sinal($dif, 2, ''), 'leitura' => ''];

    // Para os alinhamentos, quanto mais perto de zero, mais alinhado; ignora variações pequenas (ruído da medição).
    $alinhamentos = [
        ['ombros', 'Nivelamento dos ombros', 1, '°', 0.5],
        ['quadril', 'Nivelamento do quadril', 1, '°', 0.5],
        ['tronco', 'Alinhamento do tronco', 1, '°', 0.5],
        ['cabeca', 'Alinhamento da cabeça', 0, '%', 3.0],
    ];
    foreach ($alinhamentos as [$chave, $titulo, $casas, $sufixo, $tolerancia]) {
        $antes = abs((float) $anterior[$chave]);
        $agora = abs((float) $atual[$chave]);
        $mudanca = $agora - $antes;
        $leitura = abs($mudanca) < $tolerancia ? 'estável' : ($mudanca < 0 ? 'mais alinhado' : 'menos alinhado');
        $linhas[] = ['titulo' => $titulo, 'anterior' => formatar_decimal($antes, $casas) . $sufixo,
            'atual' => formatar_decimal($agora, $casas) . $sufixo, 'variacao' => $sinal($mudanca, $casas, $sufixo), 'leitura' => $leitura];
    }

    return $linhas;
}

/** Resume a comparação em uma frase: "Desde 12/09: mais alinhado: ombros; menos alinhado: tronco." */
function frase_de_comparacao(array $comparacao, string $dataAnterior): string
{
    $mais = $menos = [];
    foreach ($comparacao as $linha) {
        $nome = mb_strtolower(preg_replace('/^(Nivelamento|Alinhamento) (do|da|dos|das) /u', '', $linha['titulo']));
        if ($linha['leitura'] === 'mais alinhado') {
            $mais[] = $nome;
        } elseif ($linha['leitura'] === 'menos alinhado') {
            $menos[] = $nome;
        }
    }
    if (!$mais && !$menos) {
        return "Sem mudanças relevantes desde a avaliação anterior ($dataAnterior).";
    }

    $partes = [];
    if ($mais) {
        $partes[] = 'mais alinhado: ' . implode(', ', $mais);
    }
    if ($menos) {
        $partes[] = 'menos alinhado: ' . implode(', ', $menos);
    }
    return "Desde a avaliação anterior ($dataAnterior): " . implode('; ', $partes) . '.';
}

/** Linha do banco -> formato usado no sistema (medidas com nomes curtos). */
function avaliacao_da_linha(array $l): array
{
    return [
        'id'            => (int) $l['id'],
        'arquivo'       => $l['arquivo'],
        'criado_em'     => (new DateTime($l['criado_em']))->format('Y-m-d\TH:i:s'),
        'razao'         => (float) $l['razao_ombros_quadril'],
        'ombros'        => (float) $l['inclinacao_ombros'],
        'quadril'       => (float) $l['inclinacao_quadril'],
        'cabeca'        => (float) $l['desvio_cabeca'],
        'tronco'        => (float) $l['inclinacao_tronco'],
        'corpo_inteiro' => (bool) $l['corpo_inteiro'],
    ];
}

/** Avaliações do usuário, da mais recente para a mais antiga. */
function listar_avaliacoes(int $usuarioId, int $limite = 30): array
{
    $stmt = db()->prepare(
        'SELECT id, arquivo, razao_ombros_quadril, inclinacao_ombros, inclinacao_quadril, desvio_cabeca,
                inclinacao_tronco, corpo_inteiro, criado_em
           FROM avaliacoes_fisicas WHERE usuario_id = ?
          ORDER BY criado_em DESC, id DESC LIMIT ' . max(1, $limite)
    );
    $stmt->execute([$usuarioId]);
    return array_map('avaliacao_da_linha', $stmt->fetchAll());
}

function url_da_foto_avaliacao(int $id): string
{
    return url('fisico/avaliador_foto.php') . '?id=' . $id;
}

/** Formato enviado ao navegador: medidas, interpretação e comparação com a anterior (se houver). */
function avaliacao_para_json(array $a, ?array $anterior = null): array
{
    return [
        'id'           => $a['id'],
        'data'         => $a['criado_em'],
        'foto'         => url_da_foto_avaliacao($a['id']),
        'medidas'      => array_intersect_key($a, array_flip(['razao', 'ombros', 'quadril', 'cabeca', 'tronco', 'corpo_inteiro'])),
        'interpretacao' => interpretar_avaliacao($a),
        'comparacao'   => $anterior ? comparar_avaliacoes($a, $anterior) : null,
    ];
}

// ---------- fotos ----------

function pasta_avaliacoes(): string
{
    $pasta = BASE_PATH . '/storage/avaliacoes';
    if (!is_dir($pasta)) {
        mkdir($pasta, 0775, true);
    }
    return $pasta;
}

function caminho_foto_avaliacao(string $arquivo): string
{
    return pasta_avaliacoes() . '/' . basename($arquivo);
}

/**
 * Valida a foto enviada (um JPEG) e a guarda com nome aleatório.
 * Devolve [nome do arquivo, null] ou [null, mensagem de erro].
 */
function guardar_foto_enviada(?array $envio): array
{
    if (!$envio || ($envio['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [null, 'Não recebi a foto. Tire a foto de novo.'];
    }
    if (!is_uploaded_file($envio['tmp_name'])) {
        return [null, 'Envio inválido.'];
    }
    if ($envio['size'] > AVALIACAO_FOTO_MAX_BYTES) {
        return [null, 'A foto é grande demais. Tire outra.'];
    }

    $info = @getimagesize($envio['tmp_name']);
    $ehJpeg = $info && $info[2] === IMAGETYPE_JPEG;
    if (!$ehJpeg || $info[0] < 240 || $info[1] < 240 || $info[0] > 4000 || $info[1] > 4000) {
        return [null, 'A foto precisa ser um JPEG válido, de 240 a 4000 pixels de lado.'];
    }

    $nome = bin2hex(random_bytes(16)) . '.jpg';
    if (!move_uploaded_file($envio['tmp_name'], caminho_foto_avaliacao($nome))) {
        return [null, 'Não foi possível guardar a foto agora.'];
    }
    return [$nome, null];
}
