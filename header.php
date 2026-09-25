<!--INICIO DO CABEÇALHO-->
<style>
    
    @import url('https://fonts.googleapis.com/css2?family=Jersey+10&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');

 
header {
    background: linear-gradient(#4F0404, #B50909);
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: space-around;
    color: white;
    font-family: "Jersey 10", serif;
}

header .logo h1 {
    font-size: 36px;
    position: relative;
    top: 30px;
}

header .navegacao {
    display: flex;
    flex-direction: column;
}

header .barra-de-pesquisa {
    background-color: white;
    border-radius: 26px;
    margin-bottom: 1.2rem;
    margin-top: 19px;

}

header .navegacao input {
    border: none;
    outline: 0;
    border-radius: 26px;
    padding: 7px;
    color: black;
}

header .navegacao .barra-de-pesquisa ion-icon {
    color: black;
    margin-top: 10px;
}

header .navegacao nav {
    display: flex;
    align-items: center;
    justify-content: space-around;
    width: 297px;
    height: 22px;
    gap: 1.9rem;
}

header .navegacao nav a {
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: white;
    text-decoration: none;
}

header .navegacao nav a:hover {
    text-decoration: underline;
}

header .botao-de-login {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
    width: 260px;
    /* Ajustei a largura */
    height: 50px;
    background-color: #B50909;
    color: white;
    border-radius: 15px;
    margin-top: 40px;
    margin-bottom: 20px;
    margin-left: 00px;
    transition: background-color 0.3s, transform 0.3s;
}

header .botao-de-login:hover {
    background-color: #F39C12;
    transform: scale(1.05);
}

header .botao-de-login ion-icon {
    margin-right: 10px;
    /* Espaço entre ícone e texto */
    width: 24px;
    height: 24px;
}

header .botao-de-login a {
    text-decoration: none;
    color: white;
    font-size: 18px;
    font-weight: bold;
}
</style>
<header>
    <div class="logo">
       <a style='color:white;text-decoration:none;' href="index.php">
          <h1>IA do Bem-estar</h1>
       </a>
    </div>
    <!-- Substituímos a barra de pesquisa pelo botão de login -->
    <div class="navegacao">
        <div class="botao-de-login">
            <ion-icon name="person-outline"></ion-icon>
            <a href="register.php">FAÇA SEU LOGIN OU CADESTRE-SE</a>
        </div>
        <nav>
            <a href="fisico.php">Fisico</a>
            <a href="mental.php">Mental</a>
            <a href="ingesta.php">Ingesta</a>
        </nav>
    </div>
</header>