<?php
// Atendimento, visto pelo usuário: convites de profissionais, atendimentos em andamento e encerrados.
require_once __DIR__ . '/../includes/bootstrap.php';

$usuario = exigir_papel('usuario');

$erros = flash_get('painel_erros') ?? [];
$sucesso = flash_get('painel_ok');

$porStatus = ['pendente' => [], 'ativo' => [], 'encerrado' => []];
foreach (atendimentos_do_usuario((int) $usuario['id']) as $a) {
    $porStatus[$a['status']][] = $a;
}

function data_hora_curta(?string $datahora): string
{
    return $datahora ? (new DateTime($datahora))->format('d/m/Y \à\s H:i') : '';
}

$pageTitle = 'Atendimento';
$paginaAtiva = 'atendimento';
$pageDescription = 'Profissionais que acompanham você no LifeMap: convites, conversa e chamadas.';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container painel">
        <div class="page-head">
            <span class="eyebrow">Atendimento</span>
            <h1>Seu atendimento</h1>
            <p class="lead">Profissionais cadastrados no LifeMap (nutricionistas, educadores físicos, psicólogos…) podem convidar você para um atendimento. Nada é compartilhado até você aceitar.</p>
        </div>

        <?php if ($sucesso): ?>
            <div class="alert alert--ok" role="status"><?= e($sucesso) ?></div>
        <?php endif; ?>
        <?php if ($erros): ?>
            <div class="alert alert--erro" role="alert">
                <ul>
                    <?php foreach ($erros as $mensagem): ?>
                        <li><?= e($mensagem) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($porStatus['pendente']): ?>
            <section class="card" aria-labelledby="titulo-convites">
                <h2 id="titulo-convites">Convites para você <span class="nota">(<?= count($porStatus['pendente']) ?>)</span></h2>
                <ul class="pessoas">
                    <?php foreach ($porStatus['pendente'] as $a): ?>
                        <li class="pessoa pessoa--convite">
                            <div class="pessoa__info">
                                <strong><?= e($a['profissional_nome']) ?></strong>
                                <span><?= e($a['especialidade']) ?> · <?= e($a['registro']) ?></span>
                                <span class="nota">Convite de <?= data_hora_curta($a['convidado_em']) ?></span>
                                <p class="nota pessoa__aviso">
                                    Ao aceitar, este profissional passa a ver seu perfil (dados pessoais, IMC, plano indicado, avaliação física),
                                    seu humor dos últimos 30 dias e o resumo semanal da sua rotina, e pode conversar e ligar para você.
                                    Você pode encerrar quando quiser.
                                </p>
                            </div>
                            <div class="acoes-linha">
                                <form action="<?= url('atendimento/processar.php') ?>" method="POST">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="acao" value="aceitar">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <button type="submit" class="btn btn--primary btn--sm">Aceitar</button>
                                </form>
                                <form action="<?= url('atendimento/processar.php') ?>" method="POST">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="acao" value="recusar">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <button type="submit" class="btn btn--ghost btn--sm">Recusar</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <section class="card" aria-labelledby="titulo-ativos">
            <h2 id="titulo-ativos">Em andamento <span class="nota">(<?= count($porStatus['ativo']) ?>)</span></h2>
            <?php if (!$porStatus['ativo']): ?>
                <p>Você não tem nenhum atendimento em andamento. Quando um profissional convidar você, o convite aparece aqui e no menu.</p>
            <?php else: ?>
                <ul class="pessoas">
                    <?php foreach ($porStatus['ativo'] as $a): ?>
                        <li class="pessoa">
                            <div class="pessoa__info">
                                <strong><?= e($a['profissional_nome']) ?></strong>
                                <span><?= e($a['especialidade']) ?> · <?= e($a['registro']) ?></span>
                                <span class="nota">
                                    Desde <?= (new DateTime($a['respondido_em']))->format('d/m/Y') ?> ·
                                    <?= $a['ficha_vista_em']
                                        ? 'viu seus dados pela última vez em ' . data_hora_curta($a['ficha_vista_em'])
                                        : 'ainda não abriu seus dados' ?>
                                </span>
                            </div>
                            <div class="acoes-linha">
                                <a class="btn btn--primary btn--sm" href="<?= url('atendimento/chat.php?id=' . (int) $a['id']) ?>">
                                    Conversar
                                    <?php if ((int) $a['nao_lidas'] > 0): ?><span class="menu-badge"><?= (int) $a['nao_lidas'] ?></span><?php endif; ?>
                                </a>
                                <form action="<?= url('atendimento/processar.php') ?>" method="POST"
                                      data-confirmar="Encerrar o atendimento com <?= e($a['profissional_nome']) ?>? Ele deixa de ver seus dados e a conversa fica só para leitura.">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="acao" value="encerrar">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <button type="submit" class="btn btn--ghost btn--sm btn--perigo">Encerrar</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <?php if ($porStatus['encerrado']): ?>
            <section class="card" aria-labelledby="titulo-encerrados">
                <h2 id="titulo-encerrados">Encerrados</h2>
                <ul class="pessoas">
                    <?php foreach ($porStatus['encerrado'] as $a): ?>
                        <li class="pessoa">
                            <div class="pessoa__info">
                                <strong><?= e($a['profissional_nome']) ?></strong>
                                <span class="nota"><?= e($a['especialidade']) ?> · encerrado em <?= (new DateTime($a['encerrado_em']))->format('d/m/Y') ?></span>
                            </div>
                            <a class="btn btn--ghost btn--sm" href="<?= url('atendimento/chat.php?id=' . (int) $a['id']) ?>">Ver conversa</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
