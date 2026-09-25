<?php
session_start();

// Dados do formulário
$genero = $_POST['genero'] ?? '';
$faixa_etaria = $_POST['idade'] ?? '';
$objetivo = $_POST['objetivo'] ?? '';
$problema_saude = $_POST['problema_saude'] ?? '';
// Dados dos treinos
$treinos = [
    "18-50" => [
        "perder_peso" => [
            "treino aerobico" => ["Esteira: 20-30 minutos com intensidade moderada a alta", "Bicicleta: 20 minutos com intensidade alta (intervalos de 2 minutos intenso, 1 minuto leve).", "Pular corda: 10-15 minutos em intervalos." ],
            "circuito de treinamento" => ["Agachamento com salto", "Burpees", "Mountain climbers", "Flexão de braço", "Abdominais (crunch)"  ],
            "execicios compostos" => ["Agachamento", "Levantamento terra", "Remada com halteres", " Afundo (alternando as pernas)" ]
            
        ],
        "ganhar_peso" => [
            "Treino de Força (4 séries de 8-10 repetições com descanso de 90 segundos):" => ["Supino reto com barra", "Leg press", "Desenvolvimento de ombros com halteres", "Remada baixa (máquina)"],
            "Exercícios Isolados* (3 séries de 12 repetições com descanso de 1 minuto):" => ["Extensão de perna", "Rosca direta (bíceps)", "Elevação lateral (ombros)", "Abdominal máquina"]
    
        ],
        "ganhar_musculo"=> [
            "Treino de Hipertrofia (4 séries de 6-12 repetições com peso moderado a alto e descanso de 60-90 segundos):"=> ["Supino inclinado com barra ou halteres", "Agachamento livre ou no Smith", "Rosca martelo para bíceps", "Tríceps testa com barra W"],
            "Treino Focado em Progressão:"=> ["Deadlift (levantamento terra): 4 séries de 6-8 repetições, aumentando peso gradualmente", "Pull-ups (barra fixa): 3 séries até a falha", "Cadeira extensora para quadríceps: 4 séries de 10-12", "Stiff (para isquiotibiais): 4 séries de 8-10"],
            "Exercícios de Núcleo"=> ["Prancha: 3 séries de 1 minuto", "Abdominal com peso: 4 séries de 15"]
        ],
        "manter_saude"=> [
            "Exercícios de Baixa Intensidade"=> ["Caminhada leve ou trote: 30 minutos, 3 vezes por semana", "Alongamento geral: 10 minutos diários"],
            "Treino Funcional"=> ["Agachamento com peso corporal: 3 séries de 15", "Flexão de braço: 3 séries de 10", "Levantamento de calcanhar (panturrilha): 3 séries de 20"],
            "Exercícios de Mobilidade"=> ["Rotação de tronco: 2 séries de 15 em cada lado", "Alongamento de ombros e quadríceps", "Exercícios com minibands para glúteos e quadris."]
        ]
    ],
   "50+" => [
    "perder_peso" => [
        "treino aerobico" => [
            "Esteira: 15-20 minutos com intensidade leve a moderada", "Bicicleta: 15 minutos com intensidade moderada (intervalos de 1 minuto intenso, 2 minutos leve).", "Pular corda: 5-10 minutos em intervalos."],
            "circuito de treinamento" => ["Agachamento com salto", "Mountain climbers", "Abdominais (crunch)"],
            "exercicios compostos" => ["Agachamento", "Remada com halteres", "Afundo (alternando as pernas)"]
    ],
    "ganhar_peso" => [
        "Treino de Força (3 séries de 8-10 repetições com descanso de 90 segundos):" => ["Supino reto com barra", "Leg press", "Desenvolvimento de ombros com halteres"],
        "Exercícios Isolados* (2 séries de 12 repetições com descanso de 1 minuto):" => ["Extensão de perna", "Rosca direta (bíceps)", "Abdominal máquina"]
    ],
    "ganhar_musculo" => [
        "Treino de Hipertrofia (3 séries de 6-12 repetições com peso moderado e descanso de 60-90 segundos):" => ["Supino inclinado com barra ou halteres", "Agachamento livre ou no Smith", "Rosca martelo para bíceps"],
        "Treino Focado em Progressão:" => ["Deadlift (levantamento terra): 3 séries de 6-8 repetições, aumentando peso gradualmente", "Cadeira extensora para quadríceps: 3 séries de 10-12"],
        "Exercícios de Núcleo" => ["Prancha: 3 séries de 30 segundos", "Abdominal com peso: 3 séries de 12"]
    ],
    "manter_saude" => [
        "Exercícios de Baixa Intensidade" => ["Caminhada leve ou trote: 20-30 minutos, 3 vezes por semana", "Alongamento geral: 10 minutos diários"],
        "Treino Funcional" => ["Agachamento com peso corporal: 3 séries de 10", "Flexão de braço: 2 séries de 8", "Levantamento de calcanhar (panturrilha): 2 séries de 15"],
        "Exercícios de Mobilidade" => ["Rotação de tronco: 2 séries de 10 em cada lado", "Alongamento de ombros e quadríceps"]
    ]
]

        ];
  
   
        if (!empty($genero) && !empty($faixa_etaria) && !empty($objetivo)) {
            if (isset($treinos[$faixa_etaria][$objetivo])) {
                $treino_recomendado = $treinos[$faixa_etaria][$objetivo];
        
                // Salvar informações na sessão
                $_SESSION['treino'] = [
                    'genero' => $genero,
                    'faixa_etaria' => $faixa_etaria,
                    'objetivo' => $objetivo,
                    'problema_saude' => $problema_saude,
                    'recomendados' => $treino_recomendado
                ];
        
                // Redirecionar para a página principal
                header("Location: geradortreino.php");
                exit();
            } else {
                $_SESSION['erro'] = "Nenhum treino disponível para a combinação selecionada.";
                header("Location: geradortreino.php");
                exit();
            }
        } else {
            $_SESSION['erro'] = "Por favor, preencha todos os campos.";
            header("Location: geradortreino.php");
            exit();
        }