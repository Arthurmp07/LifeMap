<?php
// Bloco compartilhado das páginas de treino e de dieta: formulário + plano.
//
// Espera:
//   $tipo        'treino' | 'dieta'
//   $paginaUrl   URL da própria página
//   $formAction  URL do processador do formulário
//   $plano       plano a exibir (array) ou null
//   $origem      'perfil' | 'simulacao' (de onde veio $plano)
//   $erro        mensagem de erro (string) ou null
//   $faltando    o que falta no perfil para montar o plano: 'objetivo', 'maioridade'
//   $logado      bool
//   $prefill     valores do perfil para o formulário
$ehTreino = $tipo === 'treino';
$formBotao = ($logado ? 'Simular ' : 'Gerar ') . ($ehTreino ? 'treino' : 'dieta');

if ($plano) {
    $estado = '';
} elseif ($erro) {
    $estado = ' resultado--erro';
} else {
    $estado = ' resultado--vazio';
}
?>
<div class="gerador">
    <section class="card" aria-labelledby="titulo-form">
        <h2 id="titulo-form"><?= $logado ? 'Simule outra combinação' : 'Seus dados' ?></h2>
        <?php require __DIR__ . '/form_objetivo.php'; ?>
    </section>

    <section class="card resultado<?= $estado ?>" id="resultado" aria-live="polite">
        <?php if ($plano): ?>
            <h2><?= $ehTreino ? 'Seu treino' : 'Sua dieta' ?></h2>

            <div class="tags">
                <span class="tag <?= $origem === 'perfil' ? 'tag--destaque' : 'tag--simulacao' ?>">
                    <?= $origem === 'perfil' ? 'Do seu perfil' : 'Simulação' ?>
                </span>
                <span class="tag"><?= e(ROTULOS_OBJETIVO[$plano['objetivo']] ?? $plano['objetivo']) ?></span>
                <span class="tag"><?= e(ROTULOS_GENERO[$plano['genero']] ?? $plano['genero']) ?></span>
                <span class="tag"><?= e(ROTULOS_FAIXA_ETARIA[$plano['faixa_etaria']] ?? $plano['faixa_etaria']) ?></span>
            </div>

            <?php require __DIR__ . '/plano.php'; ?>

            <?php if ($origem === 'perfil'): ?>
                <p class="resultado__nota">
                    Este plano segue o seu perfil e muda quando você o atualiza.
                    <a href="<?= url('perfil/') ?>">Editar perfil</a>
                </p>
            <?php elseif ($logado): ?>
                <p class="resultado__nota">
                    Esta é apenas uma simulação.
                    <a href="<?= e($paginaUrl) ?>">Voltar ao plano do meu perfil</a>
                </p>
            <?php endif; ?>

        <?php elseif ($erro): ?>
            <p style="margin: 0;"><?= e($erro) ?></p>

        <?php elseif ($logado && $faltando): ?>
            <strong><?= $ehTreino ? 'Falta pouco para ver seu treino' : 'Falta pouco para ver sua dieta' ?></strong>
            <ul class="pendencias">
                <?php if (in_array('objetivo', $faltando, true)): ?>
                    <li>Escolha seu <strong>objetivo</strong> no perfil.</li>
                <?php endif; ?>
                <?php if (in_array('maioridade', $faltando, true)): ?>
                    <li>As sugestões atuais são para <strong>maiores de 18 anos</strong>. Confira sua data de nascimento no perfil.</li>
                <?php endif; ?>
            </ul>
            <a class="btn btn--primary" href="<?= url('perfil/') ?>">Completar meu perfil</a>

        <?php else: ?>
            <strong><?= $ehTreino ? 'Seu treino aparece aqui' : 'Sua dieta aparece aqui' ?></strong>
            <?php if ($logado): ?>
                Preencha o formulário e clique em “<?= e($formBotao) ?>”.
            <?php else: ?>
                Preencha o formulário ao lado ou <a href="<?= url('auth/login.php') ?>">entre</a> para ver o plano do seu perfil automaticamente.
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
