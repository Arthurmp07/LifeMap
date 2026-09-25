<?php
// Cartão "avaliação física": a avaliação mais recente, com a comparação com a anterior.
// Espera: $avaliacoes (da mais nova para a mais antiga; só as duas primeiras são usadas) e
//         $modo: 'proprio' (a pessoa vendo o próprio perfil, com botões) ou 'profissional' (somente leitura).
$avaliacao = $avaliacoes[0] ?? null;
$proprio = ($modo ?? 'proprio') === 'proprio';
?>
<section class="card avaliacao-perfil" aria-labelledby="titulo-avaliacao">
    <?php if ($avaliacao):
        $leitura = interpretar_avaliacao($avaliacao);
        $quando = new DateTime($avaliacao['criado_em']);
        $imagem = '<img src="' . e(url_da_foto_avaliacao($avaliacao['id'])) . '" alt="Foto da avaliação física de ' . $quando->format('d/m/Y') . '" loading="lazy">';
        ?>
        <?php if ($proprio): ?>
            <a class="avaliacao-perfil__foto" href="<?= url('fisico/avaliador.php') ?>#historico" title="Ver todas as avaliações"><?= $imagem ?></a>
        <?php else: ?>
            <div class="avaliacao-perfil__foto"><?= $imagem ?></div>
        <?php endif; ?>
        <div class="avaliacao-perfil__info">
            <h2 id="titulo-avaliacao"><?= $proprio ? 'Minha avaliação física' : 'Avaliação física' ?></h2>
            <p class="nota">Feita em <?= $quando->format('d/m/Y \à\s H:i') ?>.</p>

            <ul class="medidas medidas--compactas">
                <?php foreach ($leitura['itens'] as $item): ?>
                    <li class="medida">
                        <div class="medida__topo">
                            <strong class="medida__titulo"><?= e($item['titulo']) ?></strong>
                            <span class="medida__valor"><?= e($item['valor']) ?></span>
                            <span class="selo selo--<?= $item['estado'] ?>"><?= e($item['rotulo']) ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p class="resultado-avaliacao__resumo"><?= e($leitura['resumo']) ?></p>
            <?php if (isset($avaliacoes[1])): ?>
                <p class="nota"><?= e(frase_de_comparacao(comparar_avaliacoes($avaliacao, $avaliacoes[1]), (new DateTime($avaliacoes[1]['criado_em']))->format('d/m'))) ?></p>
            <?php endif; ?>
            <p class="nota"><?= e(AVALIACAO_AVISO) ?></p>

            <?php if ($proprio): ?>
                <div class="avaliador__acoes">
                    <a class="btn btn--primary" href="<?= url('fisico/avaliador.php') ?>">Nova avaliação</a>
                    <a class="btn btn--ghost" href="<?= url('fisico/avaliador.php') ?>#historico">Histórico e comparação</a>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif ($proprio): ?>
        <div class="avaliacao-perfil__info">
            <h2 id="titulo-avaliacao">Minha avaliação física</h2>
            <p>Você ainda não fez nenhuma avaliação. Abra a câmera, tire uma foto de frente e a foto e o resultado ficam guardados aqui, no seu perfil.</p>
            <a class="btn btn--primary" href="<?= url('fisico/avaliador.php') ?>"><ion-icon name="videocam-outline"></ion-icon> Fazer minha avaliação</a>
        </div>
    <?php else: ?>
        <div class="avaliacao-perfil__info">
            <h2 id="titulo-avaliacao">Avaliação física</h2>
            <p>Esta pessoa ainda não fez a avaliação física pela câmera.</p>
        </div>
    <?php endif; ?>
</section>
