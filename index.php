<!DOCTYPE html>
<html lang="PT-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="static/css/style.css">
    <title>Bem Estar</title>
</head>

<body>
  <?php
  include_once "header.php";
  ?>


<main>
    <section class="container">
        <!-- Card Físico -->
         <a href="fisico.php">
        <div class="card card-fisico">
            <h2>FÍSICO</h2>
            <img src="img/Fisico2.png" width="100px" height="205px"  alt="Ícone Físico">
            <p>O bem-estar físico é definido como a capacidade de realizar atividades físicas e desempenhar papéis sociais sem limitações físicas ou experiências de dor corporal e indicadores de saúde biológicos. </p>
        </div>
        </a>
        <!-- Card Mental -->
         <a href="mental.php">
        <div class="card card-mental">
            <h2>MENTAL</h2>
            <img src="img/Mental2.png" style="padding-bottom: 23.5px;" alt="Ícone Mental">
            <p>É um estado de bem-estar no qual o indivíduo é capaz de usar suas próprias habilidades para se recuperar do estresse rotineiro, para ser produtivo e contribuir com sua comunidade.   </p>
        </div>
        </a>
        <!-- Card Ingesta -->
         <a href="ingesta.php">
        <div class="card card-ingesta">
            <h2>INGESTA</h2>
            <img src="img/Ingesta.png"  alt="Ícone Ingesta">
            <p>Uma alimentação saudável é aquela que garante o fornecimento de todos os nutrientes necessários para o funcionamento do corpo. A alimentação saudável é essencial para o bem-estar do corpo humano no geral.  </p>
        </div>
        </a>
    </section>
</main>

<?php
include_once "footer.php"
?>




    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>

</html>