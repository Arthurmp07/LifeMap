<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $genero = $_POST['genero'];
    $data_nascimento = $_POST['data_nascimento'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $senha = password_hash($_POST['senha'], PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, genero, data_nascimento, email, telefone, senha) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$nome, $genero, $data_nascimento, $email, $telefone, $senha])) {
        header("Location: login.php");
        exit();
    } else {
        echo "Erro ao cadastrar. Tente novamente.";
    }
}
?>
