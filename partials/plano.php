<?php
// Conteúdo de um plano (treino ou dieta): listas e aviso de saúde.
// Espera: $tipo ('treino' | 'dieta') e $plano (formato de montar_plano()).
$ehTreino = $tipo === 'treino';
?>
<?php if ($ehTreino): ?>
    <?php foreach ($plano['recomendados'] as $categoria => $exercicios): ?>
        <h3><?= e($categoria) ?></h3>
        <ul>
            <?php foreach ($exercicios as $exercicio): ?>
                <li><?= e($exercicio) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
<?php else: ?>
    <h3>Alimentos recomendados</h3>
    <ul>
        <?php foreach ($plano['recomendados'] as $item): ?>
            <li><?= e($item) ?></li>
        <?php endforeach; ?>
    </ul>

    <h3>Alimentos a evitar</h3>
    <ul class="evitar">
        <?php foreach ($plano['evitar'] as $item): ?>
            <li><?= e($item) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($plano['problema_saude'] !== ''): ?>
    <p class="resultado__aviso">
        <strong>Atenção:</strong> você informou “<?= e($plano['problema_saude']) ?>”.
        <?= $ehTreino
            ? 'Consulte um médico ou educador físico antes de iniciar este treino.'
            : 'Converse com um médico ou nutricionista antes de mudar sua alimentação.' ?>
    </p>
<?php endif; ?>
