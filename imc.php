<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cálculo de IMC</title>
    <link rel="stylesheet" href="static/css/style.css">
    <link rel="stylesheet" href="static/css/imc.css">
</head>
<body>
    <?php include_once "header.php"; ?>
    
    <main>
    <div class="content">
        <div class="input-section">
            <h1>IMC</h1>
            <h3>Informe seu peso em kg:</h3>
            <input type="text" id="peso" class="input-peso" placeholder="Ex: 70">
            
            <h3>Informe a sua altura em metros:</h3>
            <input type="text" id="altura" class="input-altura" placeholder="Ex: 1.75">
            
            <h3>Seu resultado:</h3>
            <div class="resultado" id="resultado"><RESULTADO></div>
            
            <h3>Sua categoria:</h3>
            <div class="categoria" id="categoria"><CATEGORIA></div>

            <button id="salvar-imc" class="btn-salvar">Salvar IMC</button>
        </div>
        
        <div class="right-section">
            
        </div>
    </div>

    <div class="imc-tabela">
        <h3>Tabela de Categorias do IMC</h3>
        <table>
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th>IMC</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Abaixo do peso</td>
                    <td>Menor que 18,5</td>
                </tr>
                <tr>
                    <td>Peso normal</td>
                    <td>18,5 - 24,9</td>
                </tr>
                <tr>
                    <td>Sobrepeso</td>
                    <td>25 - 29,9</td>
                </tr>
                <tr>
                    <td>Obesidade Grau 1</td>
                    <td>30 - 34,9</td>
                </tr>
                <tr>
                    <td>Obesidade Grau 2</td>
                    <td>35 - 39,9</td>
                </tr>
                <tr>
                    <td>Obesidade Grau 3</td>
                    <td>Maior ou igual a 40</td>
                </tr>
            </tbody>
        </table>
    </div>
</main>


    <?php include_once "footer.php"; ?>

    <script>
        function calcularIMC() {
            const peso = parseFloat(document.getElementById("peso").value);
            const altura = parseFloat(document.getElementById("altura").value);
            const resultado = document.getElementById("resultado");
            const categoria = document.getElementById("categoria");
            
            if (!isNaN(peso) && !isNaN(altura) && altura > 0) {
                const imc = peso / (altura * altura);
                resultado.innerHTML = imc.toFixed(2);

                let categoriaTexto = "";
                if (imc < 18.5) {
                    categoriaTexto = "Abaixo do peso";
                } else if (imc < 25) {
                    categoriaTexto = "Peso normal";
                } else if (imc < 30) {
                    categoriaTexto = "Sobrepeso";
                } else if (imc < 35) {
                    categoriaTexto = "Obesidade Grau 1";
                } else if (imc < 40) {
                    categoriaTexto = "Obesidade Grau 2";
                } else {
                    categoriaTexto = "Obesidade Grau 3";
                }
                categoria.innerHTML = categoriaTexto;

                return imc;
            } else {
                resultado.innerHTML = "<RESULTADO>";
                categoria.innerHTML = "<CATEGORIA>";
                return null;
            }
        }

        document.getElementById("peso").addEventListener("input", calcularIMC);
        document.getElementById("altura").addEventListener("input", calcularIMC);

        document.getElementById("salvar-imc").addEventListener("click", function() {
            const imc = calcularIMC();
            if (imc !== null) {
                const formData = new FormData();
                formData.append("peso", document.getElementById("peso").value);
                formData.append("altura", document.getElementById("altura").value);
                formData.append("resultado_imc", imc);

                fetch("processar_imc.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.text())
                .then(data => alert("IMC salvo com sucesso!"))
                .catch(error => console.error("Erro ao salvar IMC:", error));
            } else {
                alert("Por favor, insira valores válidos para peso e altura.");
            }
        });
    </script>
</body>
</html>
