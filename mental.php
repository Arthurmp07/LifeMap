<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cálculo de IMC</title>
    <link rel="stylesheet" href="static/css/style.css">
    <link rel="stylesheet" href="static/css/mental.css">
</head>
<body>
    <?php include_once "header.php" ?>
    
    <main>
   
    <section class="categorias-mentais">
        <div class="categoria" style="background-color: #FFD700;" onclick="toggleContent(this)">
            <p>Ansiedade</p>
            <div class="conteudo-expandido">
                <p>A TAG é diferente dos sentimentos normais de ansiedade. Sua principal característica é uma preocupação intensa e persistente sobre situações normais e cotidianas. Quem tem este tipo de transtorno pode se preocupar incontrolavelmente com algo, várias vezes ao dia, mesmo que não haja motivo real para isso.
                Sintomas:  
                <br>
                Dificuldade de concentração; 
                <br>
                Dificuldade de dormir; 
                <br>
                Tensão muscular; 
                <br>
                Dores de estômago; 
                <br>
                Suor nas mãos, tremores, batimento cardíaco acelerado; 
                <br>
                Dormência ou formigamento em diferentes partes do corpo; 
                <br>
                Irritabilidade, fadiga e exaustão.
                <br>
                Fonte: https://www.uol.com.br/vivabem/noticias/redacao/2020/09/15/os-10-transtornos-mentais-mais-comuns-saiba-identificar-os-seus-sinais.html 
            </p>
            </div>
        </div>
        <div class="categoria" style="background-color: #FFA500;" onclick="toggleContent(this)">
            <p>Depressão</p>
            <div class="conteudo-expandido">
                <p>
                    O sintoma clássico da depressão é a tristeza prolongada, por isso é comum as pessoas dizerem que estão deprimidas quando, na verdade, estão tristes porque algo de ruim aconteceu. 
                    "Mas na depressão, a tristeza é um sentimento constante, que se manifesta pela maior parte do dia, quase diariamente, e por um período mínimo de duas semanas", especifica Antônio Geraldo da Silva, presidente eleito da Apal e diretor da ABP. 
                    Não existe um exame capaz de confirmar que alguém está deprimido. Por isso, o diagnóstico é clínico. Os sintomas da depressão podem variar de acordo com fatores como a gravidade do transtorno e a presença de condições associadas, como ansiedade, sintomas obsessivos ou psicóticos. 
                    <br>
                    Fonte: https://www.uol.com.br/vivabem/noticias/redacao/2024/08/31/depressao-tem-sintomas-claros-veja-o-que-e-e-como-tratar.html  
                </p>
            </div>
        </div>
        <div class="categoria" style="background-color: #FF8C00;" onclick="toggleContent(this)">
            <p>TOC</p>
            <div class="conteudo-expandido">
                <p>
                    O TOC é um transtorno de ansiedade caracterizado por pensamentos recorrentes e desagradáveis (obsessões) e comportamentos repetitivos ritualizados (compulsões), voltados para a redução do desconforto associado a tais pensamentos. Estudos estimam que no Brasil existem entre 3 a 4 milhões de pessoas com esse quadro.
                    Categorias de sinais: 
                    <br>    
                    Excesso de limpeza;  
                    <br>
                    Verificadores (alguém que confirma repetidamente as coisas); 
                    <br>
                    Duvidosos e pecadores (acreditam que tudo deve ser feito de maneira certa, pois caso contrário algo terrível pode acontecer como punição) 
                    <br>
                    Excesso de organização; 
                    <br>
                    Acumuladores.
                    <br>
                    Fonte: https://www.uol.com.br/vivabem/noticias/redacao/2020/09/15/os-10-transtornos-mentais-mais-comuns-saiba-identificar-os-seus-sinais.html  
                </p>
            </div>
        </div>
        <div class="categoria" style="background-color: #FF4500;" onclick="toggleContent(this)">
            <p>Transtorno Alimentar</p>
            <div class="conteudo-expandido">
                <p>
                    O transtorno alimentar retrata o total descompasso ao ingerir alimentos ou, então, a perturbação psicológica de não comer. Trata-se de uma condição que é consideravelmente grave e que causa impacto grave na saúde de quem sofre da doença.   
                    É marcado pelos comportamentos alimentares temerários. Ou a pessoa deixa de comer, por estar complexada em relação ao seu peso, ou acaba ingerindo muitos alimentos por conta de um descontrole emocional. 
                    Sendo assim, a questão emocional é muito presente no distúrbio. Normalmente, essa instabilidade decorre de traumas desenvolvidos na adolescência. Mas é possível que se apresente em outras idades.
                    <br>
                    Fonte: https://vidasaudavel.einstein.br/transtorno-alimentar/  
                </p>
            </div>
        </div>
        <div class="categoria" style="background-color: #FF0000;" onclick="toggleContent(this)">
            <p>Somatização</p>
            <div class="conteudo-expandido">
                <p>
                    Somatização é quando a mente, por meio de pensamentos e do estado emocional em conflito, manifesta dores e doenças no corpo físico. Através das nossas condições psicológicas, o corpo pode responder apresentando um problema que até então não existia. 
                    Como a somatização pode afetar: 
                    <br>
                    Dores e problemas nas articulações; 
                    <br>
                    Dores no pescoço, sensação de enrijecimento até os ombros; 
                    <br>
                    Queda no sistema imunológico; 
                    <br>
                    Dores de cabeça; 
                    <br>
                    Zumbido no ouvido; 
                    <br>
                    Dificuldade para dormir; 
                    <br>
                    Dificuldade para respirar; 
                    <br>
                    Surgimento de doenças dermatológicas; 
                    <br>
                    Enjoos e problemas no sistema digestivo. 
                </p>
            </div>
        </div>
    </section>
    
    <section class="descricao-mental">
        <h2>Importância da saúde mental:</h2>
        <p>
            A Saúde Mental de uma pessoa está relacionada à forma como ela reage às exigências da vida e ao modo como harmoniza seus desejos, capacidades, ambições, ideias e emoções. Ter saúde mental é: Estar bem consigo mesmo e com os outros. Aceitar as exigências da vida. Saber como cuidar da saúde mental é importante, sendo possível trabalhar com três pilares fundamentais: prevenção, percepção e tratamento.
        </p>
    </section>
</main>

<script>

function toggleContent(card) {
    // Fecha todos os outros cards abertos
    document.querySelectorAll('.categoria.expanded').forEach(function(openCard) {
        if (openCard !== card) openCard.classList.remove('expanded');
    });
    
    // Alterna a expansão do card clicado
    card.classList.toggle('expanded');
}

</script>


    <?php include_once "footer.php" ?>
    
</body>
</html>
