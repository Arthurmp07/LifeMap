<?php
// Sugestões de treino e de dieta (conteúdo fixo, por faixa etária e objetivo)
// e a regra que escolhe o plano do usuário a partir do perfil.

const PLANOS_TREINO = [
    "18-50" => [
        "perder_peso" => [
            "Treino aeróbico" => ["Esteira: 20-30 minutos com intensidade moderada a alta", "Bicicleta: 20 minutos com intensidade alta (intervalos de 2 minutos intenso, 1 minuto leve)", "Pular corda: 10-15 minutos em intervalos"],
            "Circuito de treinamento" => ["Agachamento com salto", "Burpees", "Mountain climbers", "Flexão de braço", "Abdominais (crunch)"],
            "Exercícios compostos" => ["Agachamento", "Levantamento terra", "Remada com halteres", "Afundo (alternando as pernas)"],
        ],
        "ganhar_peso" => [
            "Treino de força (4 séries de 8-10 repetições com descanso de 90 segundos)" => ["Supino reto com barra", "Leg press", "Desenvolvimento de ombros com halteres", "Remada baixa (máquina)"],
            "Exercícios isolados (3 séries de 12 repetições com descanso de 1 minuto)" => ["Extensão de perna", "Rosca direta (bíceps)", "Elevação lateral (ombros)", "Abdominal máquina"],
        ],
        "ganhar_musculo" => [
            "Treino de hipertrofia (4 séries de 6-12 repetições com peso moderado a alto e descanso de 60-90 segundos)" => ["Supino inclinado com barra ou halteres", "Agachamento livre ou no Smith", "Rosca martelo para bíceps", "Tríceps testa com barra W"],
            "Treino focado em progressão" => ["Deadlift (levantamento terra): 4 séries de 6-8 repetições, aumentando peso gradualmente", "Pull-ups (barra fixa): 3 séries até a falha", "Cadeira extensora para quadríceps: 4 séries de 10-12", "Stiff (para isquiotibiais): 4 séries de 8-10"],
            "Exercícios de núcleo" => ["Prancha: 3 séries de 1 minuto", "Abdominal com peso: 4 séries de 15"],
        ],
        "manter_saude" => [
            "Exercícios de baixa intensidade" => ["Caminhada leve ou trote: 30 minutos, 3 vezes por semana", "Alongamento geral: 10 minutos diários"],
            "Treino funcional" => ["Agachamento com peso corporal: 3 séries de 15", "Flexão de braço: 3 séries de 10", "Levantamento de calcanhar (panturrilha): 3 séries de 20"],
            "Exercícios de mobilidade" => ["Rotação de tronco: 2 séries de 15 em cada lado", "Alongamento de ombros e quadríceps", "Exercícios com minibands para glúteos e quadris"],
        ],
    ],
    "50+" => [
        "perder_peso" => [
            "Treino aeróbico" => ["Esteira: 15-20 minutos com intensidade leve a moderada", "Bicicleta: 15 minutos com intensidade moderada (intervalos de 1 minuto intenso, 2 minutos leve)", "Pular corda: 5-10 minutos em intervalos"],
            "Circuito de treinamento" => ["Agachamento com salto", "Mountain climbers", "Abdominais (crunch)"],
            "Exercícios compostos" => ["Agachamento", "Remada com halteres", "Afundo (alternando as pernas)"],
        ],
        "ganhar_peso" => [
            "Treino de força (3 séries de 8-10 repetições com descanso de 90 segundos)" => ["Supino reto com barra", "Leg press", "Desenvolvimento de ombros com halteres"],
            "Exercícios isolados (2 séries de 12 repetições com descanso de 1 minuto)" => ["Extensão de perna", "Rosca direta (bíceps)", "Abdominal máquina"],
        ],
        "ganhar_musculo" => [
            "Treino de hipertrofia (3 séries de 6-12 repetições com peso moderado e descanso de 60-90 segundos)" => ["Supino inclinado com barra ou halteres", "Agachamento livre ou no Smith", "Rosca martelo para bíceps"],
            "Treino focado em progressão" => ["Deadlift (levantamento terra): 3 séries de 6-8 repetições, aumentando peso gradualmente", "Cadeira extensora para quadríceps: 3 séries de 10-12"],
            "Exercícios de núcleo" => ["Prancha: 3 séries de 30 segundos", "Abdominal com peso: 3 séries de 12"],
        ],
        "manter_saude" => [
            "Exercícios de baixa intensidade" => ["Caminhada leve ou trote: 20-30 minutos, 3 vezes por semana", "Alongamento geral: 10 minutos diários"],
            "Treino funcional" => ["Agachamento com peso corporal: 3 séries de 10", "Flexão de braço: 2 séries de 8", "Levantamento de calcanhar (panturrilha): 2 séries de 15"],
            "Exercícios de mobilidade" => ["Rotação de tronco: 2 séries de 10 em cada lado", "Alongamento de ombros e quadríceps"],
        ],
    ],
];

const PLANOS_DIETA = [
    "18-50" => [
        "perder_peso" => [
            "consumir" => ["Frango", "Tilápia", "Ovos", "Batata-doce", "Quinoa", "Arroz integral", "Abacate", "Azeite", "Brócolis", "Abobrinha"],
            "evitar" => ["Frituras", "Doces", "Refrigerantes", "Alimentos ultraprocessados"],
        ],
        "ganhar_peso" => [
            "consumir" => ["Ovos inteiros", "Leite integral", "Iogurte grego", "Pão integral", "Macarrão", "Azeite", "Amendoim", "Carne vermelha"],
            "evitar" => ["Álcool", "Açúcar em excesso"],
        ],
        "ganhar_musculo" => [
            "consumir" => ["Frango", "Carne magra", "Whey protein", "Arroz branco", "Tapioca", "Batata inglesa", "Óleo de linhaça", "Sementes de chia"],
            "evitar" => ["Álcool", "Fast food", "Doces em excesso", "Frituras"],
        ],
        "manter_saude" => [
            "consumir" => ["Peixe (salmão)", "Ovos", "Lentilha", "Arroz integral", "Inhame", "Cuscuz", "Azeite", "Linhaça"],
            "evitar" => ["Refrigerantes", "Embutidos", "Excesso de sal", "Produtos com açúcar adicionado"],
        ],
    ],
    "50+" => [
        "perder_peso" => [
            "consumir" => ["Peixe grelhado", "Tofu", "Arroz integral", "Batata-doce", "Abacate", "Azeite", "Espinafre", "Couve"],
            "evitar" => ["Ultraprocessados", "Doces", "Frituras", "Refrigerantes"],
        ],
        "ganhar_peso" => [
            "consumir" => ["Iogurte", "Queijo", "Ovos inteiros", "Arroz branco", "Frutas secas", "Castanhas", "Azeite"],
            "evitar" => ["Bebidas alcoólicas", "Açúcar em excesso", "Alimentos com baixo valor nutricional"],
        ],
        "ganhar_musculo" => [
            "consumir" => ["Frango", "Atum", "Carne vermelha magra", "Quinoa", "Batata-doce", "Nozes", "Azeite"],
            "evitar" => ["Frituras", "Doces", "Produtos industrializados com aditivos químicos"],
        ],
        "manter_saude" => [
            "consumir" => ["Sardinha", "Lentilha", "Ovos", "Arroz integral", "Cenoura", "Azeite", "Sementes"],
            "evitar" => ["Excesso de sódio", "Refrigerantes", "Ultraprocessados"],
        ],
    ],
];

/**
 * Plano completo (treino ou dieta) para as escolhas dadas, no formato que as
 * páginas exibem. Devolve null se não houver conteúdo para a combinação.
 */
function montar_plano(string $tipo, string $genero, string $faixa, string $objetivo, string $problemaSaude = ''): ?array
{
    if ($tipo === 'treino') {
        $categorias = PLANOS_TREINO[$faixa][$objetivo] ?? null;
        $conteudo = $categorias === null ? null : ['recomendados' => $categorias];
    } else {
        $dieta = PLANOS_DIETA[$faixa][$objetivo] ?? null;
        $conteudo = $dieta === null ? null : ['recomendados' => $dieta['consumir'], 'evitar' => $dieta['evitar']];
    }

    if ($conteudo === null) {
        return null;
    }

    return [
        'genero'         => $genero,
        'faixa_etaria'   => $faixa,
        'objetivo'       => $objetivo,
        'problema_saude' => $problemaSaude,
    ] + $conteudo;
}

/**
 * Escolhas guardadas no perfil do usuário logado (null se ninguém estiver logado).
 * 'faltando' lista o que impede de montar o plano: 'objetivo' e/ou 'maioridade'
 * (os planos cobrem apenas maiores de 18 anos).
 */
function escolhas_do_perfil(): ?array
{
    $u = usuario_atual();
    if (!$u) {
        return null;
    }

    $escolhas = [
        'genero'         => $u['genero'],
        'idade'          => faixa_etaria_de($u['data_nascimento']),
        'objetivo'       => $u['objetivo'],
        'problema_saude' => trim((string) $u['problema_saude']),
    ];

    $faltando = [];
    if (!$escolhas['objetivo']) {
        $faltando[] = 'objetivo';
    }
    if (!$escolhas['idade']) {
        $faltando[] = 'maioridade';
    }

    return ['escolhas' => $escolhas, 'faltando' => $faltando];
}

/**
 * Plano do perfil para 'treino' ou 'dieta'.
 *   null                      -> ninguém logado
 *   ['faltando' => [...]]     -> perfil incompleto
 *   ['plano' => [...]]        -> plano pronto
 */
function plano_do_perfil(string $tipo): ?array
{
    $perfil = escolhas_do_perfil();
    if ($perfil === null) {
        return null;
    }
    if ($perfil['faltando']) {
        return ['faltando' => $perfil['faltando']];
    }

    $e = $perfil['escolhas'];
    return ['faltando' => [], 'plano' => montar_plano($tipo, $e['genero'], $e['idade'], $e['objetivo'], $e['problema_saude'])];
}

/**
 * Treino e dieta indicados pelo perfil do usuário logado.
 * Devolve ['treino' => plano|null, 'dieta' => plano|null, 'faltando' => [...]].
 */
function planos_do_perfil(): array
{
    $perfil = escolhas_do_perfil();
    if ($perfil === null || $perfil['faltando']) {
        return ['treino' => null, 'dieta' => null, 'faltando' => $perfil['faltando'] ?? []];
    }

    $e = $perfil['escolhas'];
    return [
        'treino'   => montar_plano('treino', $e['genero'], $e['idade'], $e['objetivo'], $e['problema_saude']),
        'dieta'    => montar_plano('dieta', $e['genero'], $e['idade'], $e['objetivo'], $e['problema_saude']),
        'faltando' => [],
    ];
}

/** Valores do perfil para pré-preencher os formulários dos geradores (só o que estiver preenchido). */
function perfil_para_formulario(): array
{
    $perfil = escolhas_do_perfil();
    if ($perfil === null) {
        return [];
    }
    return array_filter($perfil['escolhas'], fn($valor) => $valor !== null && $valor !== '');
}

/**
 * Lê o formulário dos geradores e guarda na sessão o plano (ou a mensagem de
 * erro) para a página de destino exibir uma única vez.
 */
function guardar_plano_do_formulario(string $tipo): void
{
    $genero = $_POST['genero'] ?? '';
    $faixa = $_POST['idade'] ?? '';
    $objetivo = $_POST['objetivo'] ?? '';
    $problemaSaude = trim($_POST['problema_saude'] ?? '');

    if (!isset(ROTULOS_GENERO[$genero]) || $faixa === '' || $objetivo === '') {
        $_SESSION['erro'] = 'Por favor, preencha todos os campos.';
        return;
    }

    $plano = montar_plano($tipo, $genero, $faixa, $objetivo, mb_substr($problemaSaude, 0, 255));
    if ($plano === null) {
        $_SESSION['erro'] = $tipo === 'treino'
            ? 'Nenhum treino disponível para a combinação selecionada.'
            : 'Nenhuma dieta disponível para a combinação selecionada.';
        return;
    }

    $_SESSION[$tipo] = $plano;
}
