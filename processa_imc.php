<?php
// Inclui a conexão com o banco de dados
require 'db.php';

// Verifica se todos os dados foram enviados via POST
if (isset($_POST['peso'], $_POST['altura'], $_POST['resultado_imc'])) {
    // Obtém os valores do formulário
    $peso = floatval($_POST['peso']);
    $altura = floatval($_POST['altura']);
    $imc = floatval($_POST['resultado_imc']);
    
    try {
        // Prepara a consulta para inserir os dados na tabela
        $sql = "INSERT INTO imc (peso, altura, imc) VALUES (:peso, :altura, :imc)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':peso', $peso);
        $stmt->bindParam(':altura', $altura);
        $stmt->bindParam(':imc', $imc);
        
        // Executa a consulta
        if ($stmt->execute()) {
            echo "IMC salvo com sucesso!";
        } else {
            echo "Erro ao salvar o IMC. Tente novamente.";
        }
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
} else {
    echo "Dados incompletos. Certifique-se de preencher peso, altura e IMC.";
}
?>
