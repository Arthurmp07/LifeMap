<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$logado = usuario_logado();

// Altura para pré-preencher: a do perfil ou, na falta dela, a do último cálculo salvo.
$alturaPadrao = null;
if ($logado) {
    $usuario = usuario_atual();
    if ($usuario && $usuario['altura'] !== null) {
        $alturaPadrao = (float) $usuario['altura'];
    } elseif ($usuario) {
        $stmt = db()->prepare('SELECT altura FROM imc WHERE usuario_id = ? ORDER BY criado_em DESC, id DESC LIMIT 1');
        $stmt->execute([$usuario['id']]);
        $ultima = $stmt->fetchColumn();
        $alturaPadrao = $ultima !== false ? round((float) $ultima, 2) : null;
    }
}

// Dados que o assets/js/imc.js lê.
$configJs = [
    'logado'       => $logado,
    'alturaPadrao' => $alturaPadrao,
    'categorias'   => IMC_CATEGORIAS,
    'limites'      => ['pesoMin' => PESO_MIN, 'pesoMax' => PESO_MAX, 'alturaMin' => ALTURA_MIN, 'alturaMax' => ALTURA_MAX],
    'api'          => url('fisico/imc_api.php'),
    'login'        => url('auth/login.php'),
];

$pageTitle = 'Cálculo de IMC';
$paginaAtiva = 'fisico';
$bodyClass = 'tema-fisico';
$pageDescription = 'Calcule seu Índice de Massa Corporal, veja em qual categoria você está e acompanhe sua evolução.';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container">
        <div class="page-head">
            <p class="breadcrumb"><a href="<?= url('fisico/') ?>">← Físico</a></p>
            <span class="eyebrow">Físico</span>
            <h1>Cálculo de IMC</h1>
            <p class="lead">Informe seu peso e sua altura. O resultado aparece enquanto você digita.</p>
        </div>

        <div class="imc">
            <section class="card" aria-labelledby="titulo-dados">
                <h2 id="titulo-dados">Seus dados</h2>

                <div class="field">
                    <label for="peso">Peso (kg)</label>
                    <input class="input" type="text" id="peso" inputmode="decimal" autocomplete="off" placeholder="Ex.: 70">
                </div>
                <div class="field">
                    <label for="altura">Altura (m)</label>
                    <input class="input" type="text" id="altura" inputmode="decimal" autocomplete="off" placeholder="Ex.: 1,75">
                    <p class="field__hint" id="imc-aviso" role="alert" hidden>Confira os valores: peso entre 20 e 500 kg e altura entre 0,5 e 2,8 m.</p>
                </div>

                <button type="button" class="btn btn--primary btn--block" id="salvar-imc" disabled>Salvar IMC</button>
                <?php if (!$logado): ?>
                    <p class="field__hint" style="margin-top: .75rem;">
                        <a href="<?= url('auth/login.php') ?>">Entre</a> ou <a href="<?= url('auth/register.php') ?>">cadastre-se</a> para salvar seu resultado.
                    </p>
                <?php endif; ?>
            </section>

            <section class="card" aria-labelledby="titulo-resultado" aria-live="polite">
                <h2 id="titulo-resultado">Seu resultado</h2>
                <div class="imc__valor" id="resultado">—</div>
                <span class="imc__categoria" id="categoria">Preencha peso e altura</span>

                <div class="escala" id="escala" aria-hidden="true">
                    <div class="escala__barra">
                        <span class="escala__seg cat-abaixo" style="flex: 3.5 1 0"></span>
                        <span class="escala__seg cat-normal" style="flex: 6.5 1 0"></span>
                        <span class="escala__seg cat-sobrepeso" style="flex: 5 1 0"></span>
                        <span class="escala__seg cat-ob1" style="flex: 5 1 0"></span>
                        <span class="escala__seg cat-ob2" style="flex: 5 1 0"></span>
                        <span class="escala__seg cat-ob3" style="flex: 5 1 0"></span>
                        <span class="escala__marcador" id="marcador"></span>
                    </div>
                    <div class="escala__legenda">
                        <span style="left: 11.67%">18,5</span>
                        <span style="left: 33.33%">25</span>
                        <span style="left: 50%">30</span>
                        <span style="left: 66.67%">35</span>
                        <span style="left: 83.33%">40</span>
                    </div>
                </div>

                <p class="nota" style="margin: 1.5rem 0 0;">O IMC é uma referência geral: não considera massa muscular, idade ou distribuição de gordura. Converse com um profissional de saúde para uma avaliação completa.</p>
            </section>
        </div>

        <section class="card" id="historico" style="margin-top: 1.5rem;" aria-labelledby="titulo-historico">
            <h2 id="titulo-historico">Sua evolução</h2>
            <?php if ($logado): ?>
                <div id="hist-conteudo" aria-live="polite">
                    <p class="hist__vazio">Carregando seu histórico…</p>
                </div>
            <?php else: ?>
                <p class="hist__vazio">Entre na sua conta para salvar seus cálculos e acompanhar a evolução do IMC ao longo do tempo.</p>
                <div class="hist__acoes">
                    <a class="btn btn--primary" href="<?= url('auth/login.php') ?>">Entrar</a>
                    <a class="btn btn--ghost" href="<?= url('auth/register.php') ?>">Criar conta</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="card" style="margin-top: 1.5rem;" aria-labelledby="titulo-tabela">
            <h2 id="titulo-tabela">Categorias do IMC</h2>
            <table class="tabela tabela--cat">
                <thead>
                    <tr><th scope="col">Categoria</th><th scope="col">IMC (kg/m²)</th></tr>
                </thead>
                <tbody>
                    <?php foreach (IMC_CATEGORIAS as $categoria): ?>
                        <tr class="cat-<?= $categoria['chave'] ?>" data-cat="<?= $categoria['chave'] ?>">
                            <td><?= e($categoria['nome']) ?></td>
                            <td><?= e($categoria['faixa']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</main>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script type="application/json" id="imc-config"><?= json_encode($configJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="<?= asset('js/grafico-imc.js') ?>"></script>
<script src="<?= asset('js/imc.js') ?>"></script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
