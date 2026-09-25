<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingesta</title>
    <link rel="stylesheet" href="static/css/ingesta.css">
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    <?php include_once "header.php"?>
    
    <!-- Botão "Gerador de Dieta" separado na parte superior -->
    <section class="diet-button">
        <a href="geradordieta.php" class="diet-link">
            <div class="icon"><ion-icon name="restaurant-outline"><img src="img/Ingesta.png" width="50px" alt=""></ion-icon></div>
            <span>Gerador de Dieta</span>
            <div class="icon"><ion-icon name="restaurant-outline"><img src="img/Ingesta.png" width="50px" alt=""></ion-icon></div>
        </a>
    </section>
    
    <main>
        <section class="diet-generator">
            <div class="diet-info">
                <div clamg src="img/Ingesta.png" alt="Pirâmide Alimentar">
                </div>
                <div class="description">
                    <h3>Pirâmide Alimentar, o que é:</h3>
                    <p>A pirâmide alimentar é uma representação gráfica que reúne informações importantes a respeito dos grupos de alimentos presentes em nossa dieta. Seu principal objetivo é garantir o bem-estar nutricional da população, informando-a, principalmente, sobre as porções recomendadas de cada tipo de alimento. Na pirâmide alimentar, os alimentos estão dispostos por nível de necessidade (base: maior necessidade/importância e topo: menor importância/necessidade), sendo eles:  
                        Primeiro nível (base da pirâmide): grupo da água; 
                        <br>
                        Segundo nível: gss="pyramid">
                    <irupo dos cereais, tubérculos, raízes; 
                        <br>
                        Terceiro nível: grupo das hortaliças e grupo das frutas; 
                        <br>
                        Quarto nível: grupo do leite e produtos lácteos, grupo das carnes e ovos, grupo das leguminosas e oleaginosas; 
                        <br>
                        Quinto nível (topo da pirâmide): grupo dos óleos e gorduras, grupo dos açúcares e doces.
                    </p>
                </div>
            </div>
            <div class="health-importance">
                <h3>Importância da saúde alimentar:</h3>
                <p>A nutrição faz parte da vida de todo ser humano, com a ingestão de alimentos saudáveis, o corpo recebe os nutrientes, vitaminas e minerais necessários para manter o funcionamento adequado, inclusive prevenindo doenças como obesidade, anemia, diabetes, entre outras. </p>
            </div>
        </section>
    </main>
    <?php include_once "footer.php"?>
</body>
</html>
