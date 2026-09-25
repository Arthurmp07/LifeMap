<?php
// Formulário compartilhado pelos geradores de treino e de dieta.
// Espera: $formAction (URL do POST) e $formBotao (texto do botão).
// Opcional: $prefill (genero, idade, objetivo, problema_saude), vindo do perfil.
$prefill = $prefill ?? [];
?>
<?php if ($prefill): ?>
    <p class="alert alert--ok">
        Preenchemos com os dados do seu <a href="<?= url('perfil/') ?>">perfil</a>. Você pode alterar o que quiser.
    </p>
<?php endif; ?>

<form class="form-gerador" method="POST" action="<?= e($formAction) ?>">
    <fieldset class="fieldset">
        <legend>Gênero</legend>
        <div class="chip-group">
            <?php foreach (ROTULOS_GENERO as $valor => $rotulo): ?>
                <label class="chip">
                    <input type="radio" name="genero" value="<?= $valor ?>"<?= ($prefill['genero'] ?? '') === $valor ? ' checked' : '' ?> required>
                    <span><?= $rotulo ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <fieldset class="fieldset">
        <legend>Faixa de idade</legend>
        <div class="chip-group">
            <?php foreach (ROTULOS_FAIXA_ETARIA as $valor => $rotulo): ?>
                <label class="chip">
                    <input type="radio" name="idade" value="<?= $valor ?>"<?= ($prefill['idade'] ?? '') === $valor ? ' checked' : '' ?> required>
                    <span><?= $rotulo ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <fieldset class="fieldset">
        <legend>Objetivo</legend>
        <div class="chip-group">
            <?php foreach (ROTULOS_OBJETIVO as $valor => $rotulo): ?>
                <label class="chip">
                    <input type="radio" name="objetivo" value="<?= $valor ?>"<?= ($prefill['objetivo'] ?? '') === $valor ? ' checked' : '' ?> required>
                    <span><?= $rotulo ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <div class="field">
        <label for="problema_saude">Problema de saúde <span class="field__optional">(opcional)</span></label>
        <input class="input" type="text" id="problema_saude" name="problema_saude" maxlength="255"
               value="<?= e($prefill['problema_saude'] ?? '') ?>"
               placeholder="Ex.: dor no joelho, hipertensão, diabetes…">
    </div>

    <button type="submit" class="btn btn--primary btn--lg"><?= e($formBotao) ?></button>
</form>
