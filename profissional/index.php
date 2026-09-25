<?php
// Painel do profissional: busca de usuários para convidar, convites enviados e pacientes em atendimento.
require_once __DIR__ . '/../includes/bootstrap.php';

$profissional = exigir_papel('profissional');
$profissionalId = (int) $profissional['id'];

$stmt = db()->prepare('SELECT especialidade, registro FROM profissionais WHERE usuario_id = ?');
$stmt->execute([$profissionalId]);
$cadastro = $stmt->fetch() ?: ['especialidade' => '', 'registro' => ''];

$erros = flash_get('painel_erros') ?? [];
$sucesso = flash_get('painel_ok');

$busca = mb_substr(trim((string) ($_GET['busca'] ?? '')), 0, 100);
$buscou = mb_strlen($busca) >= BUSCA_MIN_CARACTERES;
$resultados = $buscou ? buscar_usuarios_para_convite($profissionalId, $busca) : [];

$porStatus = ['ativo' => [], 'pendente' => [], 'encerrado' => [], 'recusado' => []];
foreach (atendimentos_do_profissional($profissionalId) as $a) {
    $porStatus[$a['status']][] = $a;
}

function data_curta(?string $datahora): string
{
    return $datahora ? (new DateTime($datahora))->format('d/m/Y') : '';
}

$pageTitle = 'Pacientes';
$paginaAtiva = 'pacientes';
$pageDescription = 'Convide usuários, veja os dados de quem você atende e converse com eles.';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container painel">
        <div class="page-head">
            <span class="eyebrow"><?= e($cadastro['especialidade']) ?> · <?= e($cadastro['registro']) ?></span>
            <h1>Pacientes</h1>
            <p class="lead">Convide um usuário para começar um atendimento. Você só vê os dados de saúde de quem aceitar o convite, e só enquanto o atendimento estiver em andamento.</p>
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

        <section class="card" aria-labelledby="titulo-convidar">
            <h2 id="titulo-convidar">Convidar um usuário</h2>
            <form class="busca" action="<?= url('profissional/') ?>" method="GET" role="search">
                <label class="visually-hidden" for="busca">Nome ou e-mail do usuário</label>
                <input class="input" type="search" id="busca" name="busca" value="<?= e($busca) ?>" maxlength="100"
                       placeholder="Nome ou e-mail do usuário (mínimo <?= BUSCA_MIN_CARACTERES ?> letras)" autocomplete="off">
                <button type="submit" class="btn btn--primary"><ion-icon name="search-outline"></ion-icon> Buscar</button>
            </form>
            <p class="nota">A busca mostra só o nome e parte do e-mail. Os dados de saúde ficam bloqueados até a pessoa aceitar o convite.</p>

            <?php if ($busca !== '' && !$buscou): ?>
                <p class="busca__vazio">Digite pelo menos <?= BUSCA_MIN_CARACTERES ?> letras.</p>
            <?php elseif ($buscou && !$resultados): ?>
                <p class="busca__vazio">Nenhum usuário encontrado para “<?= e($busca) ?>”.</p>
            <?php elseif ($resultados): ?>
                <ul class="pessoas">
                    <?php foreach ($resultados as $r): ?>
                        <li class="pessoa">
                            <div class="pessoa__info">
                                <strong><?= e($r['nome']) ?></strong>
                                <span class="nota"><?= e($r['email']) ?></span>
                            </div>
                            <?php if ($r['status'] === 'ativo'): ?>
                                <span class="selo selo--ok">Em atendimento</span>
                            <?php elseif ($r['status'] === 'pendente'): ?>
                                <span class="selo selo--leve">Convite enviado</span>
                            <?php else: ?>
                                <form action="<?= url('atendimento/processar.php') ?>" method="POST">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="acao" value="convidar">
                                    <input type="hidden" name="usuario_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="busca" value="<?= e($busca) ?>">
                                    <button type="submit" class="btn btn--primary btn--sm"><?= $r['status'] ? 'Convidar de novo' : 'Convidar' ?></button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="card" aria-labelledby="titulo-ativos">
            <h2 id="titulo-ativos">Em atendimento <span class="nota">(<?= count($porStatus['ativo']) ?>)</span></h2>
            <?php if (!$porStatus['ativo']): ?>
                <p>Você ainda não atende ninguém. Convide um usuário acima; quando ele aceitar, aparece aqui.</p>
            <?php else: ?>
                <ul class="pessoas">
                    <?php foreach ($porStatus['ativo'] as $a): ?>
                        <li class="pessoa">
                            <div class="pessoa__info">
                                <strong><?= e($a['usuario_nome']) ?></strong>
                                <span class="nota">Atendimento desde <?= data_curta($a['respondido_em']) ?></span>
                            </div>
                            <div class="acoes-linha">
                                <a class="btn btn--primary btn--sm" href="<?= url('profissional/paciente.php?id=' . (int) $a['usuario_id']) ?>">Ver ficha</a>
                                <a class="btn btn--ghost btn--sm" href="<?= url('atendimento/chat.php?id=' . (int) $a['id']) ?>">
                                    Conversar
                                    <?php if ((int) $a['nao_lidas'] > 0): ?><span class="menu-badge"><?= (int) $a['nao_lidas'] ?></span><?php endif; ?>
                                </a>
                                <form action="<?= url('atendimento/processar.php') ?>" method="POST"
                                      data-confirmar="Encerrar o atendimento com <?= e($a['usuario_nome']) ?>? Você deixa de ver os dados dessa pessoa.">
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

        <?php if ($porStatus['pendente']): ?>
            <section class="card" aria-labelledby="titulo-pendentes">
                <h2 id="titulo-pendentes">Convites aguardando resposta <span class="nota">(<?= count($porStatus['pendente']) ?>)</span></h2>
                <ul class="pessoas">
                    <?php foreach ($porStatus['pendente'] as $a): ?>
                        <li class="pessoa">
                            <div class="pessoa__info">
                                <strong><?= e($a['usuario_nome']) ?></strong>
                                <span class="nota">Enviado em <?= data_curta($a['convidado_em']) ?></span>
                            </div>
                            <form action="<?= url('atendimento/processar.php') ?>" method="POST">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="acao" value="cancelar_convite">
                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <button type="submit" class="btn btn--ghost btn--sm">Cancelar convite</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($porStatus['encerrado'] || $porStatus['recusado']): ?>
            <section class="card" aria-labelledby="titulo-antigos">
                <h2 id="titulo-antigos">Encerrados e recusados</h2>
                <ul class="pessoas">
                    <?php foreach ($porStatus['encerrado'] as $a): ?>
                        <li class="pessoa">
                            <div class="pessoa__info">
                                <strong><?= e($a['usuario_nome']) ?></strong>
                                <span class="nota">Atendimento encerrado em <?= data_curta($a['encerrado_em']) ?></span>
                            </div>
                            <a class="btn btn--ghost btn--sm" href="<?= url('atendimento/chat.php?id=' . (int) $a['id']) ?>">Ver conversa</a>
                        </li>
                    <?php endforeach; ?>
                    <?php foreach ($porStatus['recusado'] as $a): ?>
                        <li class="pessoa">
                            <div class="pessoa__info">
                                <strong><?= e($a['usuario_nome']) ?></strong>
                                <span class="nota">Recusou o convite em <?= data_curta($a['respondido_em']) ?></span>
                            </div>
                            <span class="selo selo--info">Recusado</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
