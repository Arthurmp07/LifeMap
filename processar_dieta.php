<?php
session_start();
require 'db.php';

// Dados das dietas
$dietas = [
    "18-50" => [
        "perder_peso" => [
            "consumir" => ["Frango", "Tilápia", "Ovos", "Batata-doce", "Quinoa", "Arroz integral", "Abacate", "Azeite", "Brócolis", "Abobrinha"],
            "evitar" => ["Frituras", "Doces", "Refrigerantes", "Alimentos ultraprocessados"]
        ],
        "ganhar_peso" => [
            "consumir" => ["Ovos inteiros", "Leite integral", "Iogurte grego", "Pão integral", "Macarrão", "Azeite", "Amendoim", "Carne vermelha"],
            "evitar" => ["Álcool", "Açúcar em excesso"]
        ],
        "ganhar_musculo"=> [
            "consumir"=> ["Frango", "Carne magra", "Whey protein", "Arroz branco", "Tapioca", 
                         "Batata inglesa", "Óleo de linhaça", "Sementes de chia"],
            "evitar"=> ["Álcool", "Fast food", "Doces em excesso", "Frituras"]
        ],
        "manter_saude"=> [
            "consumir"=> ["Peixe (salmão)", "Ovos", "Lentilha", "Arroz integral", "Inhame", 
                         "Cuscuz", "Azeite", "Linhaça"],
            "evitar"=> ["Refrigerantes", "Embutidos", "Excesso de sal", "Produtos com açúcar adicionado"]
        ]
    ],
    "50+"=> [
        "perder_peso"=> [
            "consumir"=> ["Peixe grelhado", "Tofu", "Arroz integral", "Batata-doce", 
                         "Abacate", "Azeite", "Espinafre", "Couve"],
            "evitar"=> ["Ultraprocessados", "Doces", "Frituras", "Refrigerantes"]
        ],
        "ganhar_peso"=> [
            "consumir"=> ["Iogurte", "Queijo", "Ovos inteiros", "Arroz branco", "Frutas secas", 
                         "Castanhas", "Azeite"],
            "evitar"=> ["Bebidas alcoólicas", "Açúcar em excesso", "Alimentos com baixo valor nutricional"]
    ],
        "ganhar_musculo"=> [
            "consumir"=> ["Frango", "Atum", "Carne vermelha magra", "Quinoa", "Batata-doce", 
                         "Nozes", "Azeite"],
            "evitar"=> ["Frituras", "Doces", "Produtos industrializados com aditivos químicos"]
],
        "manter_saude"=> [
            "consumir"=> ["Sardinha", "Lentilha", "Ovos", "Arroz integral", "Cenoura", 
                         "Azeite", "Sementes"],
            "evitar"=> ["Excesso de sódio", "Refrigerantes", "Ultraprocessados"]
        ]
    ]
        ];
   

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $genero = $_POST['genero'];
            $faixa_etaria = $_POST['idade'];
            $objetivo = $_POST['objetivo'];
            $problema_saude = $_POST['problema_saude'];
        
            if (isset($dietas[$faixa_etaria][$objetivo])) {
                $dieta = $dietas[$faixa_etaria][$objetivo];
        
                // Salvar na sessão
                $_SESSION['dieta'] = [
                    'objetivo' => $objetivo,
                    'genero' => $genero,
                    'problema_saude' => $problema_saude,
                    'recomendados' => $dieta['consumir'],
                    'evitar' => $dieta['evitar']
                ];
        
                // Redirecionar para exibir a dieta na página original
                header("Location: geradordieta.php");
                exit();
            } else {
                $_SESSION['erro'] = "Nenhuma dieta disponível para a combinação selecionada.";
                header("Location: geradordieta.php");
                exit();
            }
        }