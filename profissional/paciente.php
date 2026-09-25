<?php
// Ficha do paciente: o que o profissional vê de um usuário com atendimento ATIVO.
require_once __DIR__ . '/../includes/bootstrap.php';

$profissional = exigir_papel('profissional');

$pacienteId = (int) ($_GET['id'] ?? 0);
$atendimento = $pacienteId > 0 ? ficha_liberada_para((int) $profissional['id'], $pacienteId) : null;
$paciente = $atendimento ? dados_do_paciente($pacienteId) : null;

if (!$atendimento || !$paciente) {
    // Sem atendimento ativo (ou pessoa que não existe): mesma resposta nos dois casos.
    http_response_code(404);
    $pageTitle = 'Paciente não encontrado';
    $paginaAtiva = 'pacientes';
    require __DIR__ . '/../partials/head.php';
    require __DIR__ . '/../partials/header.php';
    ?>
    <main id="conteudo">
        <div class="container auth">
            <section class="card auth__card">
                <h1>Paciente não encontrado</h1>
                <p class="auth__lead">Você só vê os dados de quem aceitou o seu convite e está em atendimento com você agora.</p>
                <a class="btn btn--primary btn--lg btn--block" href="<?= url('profissional/') ?>">Voltar aos pacientes</a>
            </section>
        </div>
    </main>
    <?php
    require __DIR__ . '/../partials/footer.php';
    exit();
}

// Cada abertura da ficha fica registrada; o próprio usuário vê a data na página de atendimento dele.
registrar_visita_da_ficha((int) $atendimento['id']);

$idade = idade_de($paciente['data_nascimento']);
$imcs = historico_imc($pacienteId);
$ultimoImc = $imcs ? $imcs[array_key_last($imcs)] : null;
$planos = planos_do_usuario($paciente);
$avaliacoes = listar_avaliacoes($pacienteId, 2);
$humor = humor_recente($pacienteId);
$semana = resumo_da_semana($pacienteId);

$comHumor = array_values(array_filter($humor, fn($d) => $d['nivel'] !== null));
$mediaHumor = $comHumor ? array_sum(array_column($comHumor, 'nivel')) / count($comHumor) : null;
$anotacoes = array_slice(array_reverse(array_filter($comHumor, fn($d) => $d['nota'] !== '')), 0, 5);

$maiorTempo = max(1, max(array_column($semana['categorias'], 'minutos')));

$configJs = ['registros' => $imcs, 'categorias' => IMC_CATEGORIAS];

$pageTitle = $paciente['nome'];
$paginaAtiva = 'pacientes';
$pageDescription = 'Ficha do paciente.';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container painel">
        <div class="page-head page-head--acoes">
            <div>
                <p class="breadcrumb"><a href="<?= url('profissional/') ?>">← Pacientes</a></p>
                <span class="eyebrow">Ficha do paciente</span>
                <h1><?= e($paciente['nome']) ?></h1>
                <p class="lead">Em atendimento desde <?= (new DateTime($atendimento['respondido_em']))->format('d/m/Y') ?>. Você vê estes dados enquanto o atendimento estiver em andamento.</p>
            </div>
            <div class="acoes-linha">
                <a class="btn btn--primary" href="<?= url('atendimento/chat.php?id=' . (int) $atendimento['id']) ?>"><ion-icon name="chatbubbles-outline"></ion-icon> Conversar</a>
                <form action="<?= url('atendimento/processar.php') ?>" method="POST"
                      data-confirmar="Encerrar o atendimento com <?= e($paciente['nome']) ?>? Você deixa de ver os dados dessa pessoa.">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="acao" value="encerrar">
                    <input type="hidden" name="id" value="<?= (int) $atendimento['id'] ?>">
                    <button type="submit" class="btn btn--ghost btn--perigo">Encerrar atendimento</button>
                </form>
            </div>
        </div>

        <section class="card" aria-labelledby="titulo-dados">
            <h2 id="titulo-dados">Dados pessoais</h2>
            <dl class="dados">
                <div><dt>Idade</dt><dd><?= $idade !== null ? $idade . ' anos' : 'Não informada' ?></dd></div>
                <div><dt>Gênero</dt><dd><?= e($paciente['genero'] ? ROTULOS_GENERO[$paciente['genero']] : 'Não informado') ?></dd></div>
                <div><dt>E-mail</dt><dd><?= e($paciente['email']) ?></dd></div>
                <div><dt>Telefone</dt><dd><?= e($paciente['telefone'] ?: 'Não informado') ?></dd></div>
                <div><dt>Altura</dt><dd><?= $paciente['altura'] !== null ? formatar_decimal((float) $paciente['altura'], 2) . ' m' : 'Não informada' ?></dd></div>
                <div><dt>Objetivo</dt><dd><?= e($paciente['objetivo'] ? ROTULOS_OBJETIVO[$paciente['objetivo']] : 'Não definido') ?></dd></div>
                <div class="dados__largo"><dt>Problema de saúde</dt><dd><?= e(trim((string) $paciente['problema_saude']) !== '' ? $paciente['problema_saude'] : 'Nada informado') ?></dd></div>
            </dl>
        </section>

        <section class="card" aria-labelledby="titulo-imc">
            <h2 id="titulo-imc">Evolução do IMC</h2>
            <?php if (!$ultimoImc): ?>
                <p>Esta pessoa ainda não salvou nenhum cálculo de IMC.</p>
            <?php else:
                $categoria = imc_categoria((float) $ultimoImc['imc']);
                ?>
                <div class="stat">
                    <span class="stat__label">Registro mais recente · <?= (new DateTime($ultimoImc['data']))->format('d/m/Y') ?></span>
                    <span class="stat__value"><?= formatar_decimal((float) $ultimoImc['imc']) ?></span>
                    <span class="imc__categoria cat-<?= $categoria['chave'] ?>"><?= e($categoria['nome']) ?></span>
                    <span class="stat__delta"><?= formatar_peso((float) $ultimoImc['peso']) ?> kg · <?= formatar_decimal((float) $ultimoImc['altura'], 2) ?> m</span>
                </div>
                <div class="grafico" id="ficha-grafico"></div>

                <h3 class="hist__titulo">Últimos registros</h3>
                <div class="tabela-rolagem">
                    <table class="tabela tabela--registros">
                        <thead>
                            <tr><th scope="col">Data</th><th scope="col">Peso</th><th scope="col">Altura</th><th scope="col">IMC</th><th scope="col">Categoria</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice(array_reverse($imcs), 0, 10) as $r):
                                $c = imc_categoria((float) $r['imc']); ?>
                                <tr>
                                    <td><?= (new DateTime($r['data']))->format('d/m/Y') ?></td>
                                    <td><?= formatar_peso((float) $r['peso']) ?> kg</td>
                                    <td><?= formatar_decimal((float) $r['altura'], 2) ?> m</td>
                                    <td><strong><?= formatar_decimal((float) $r['imc']) ?></strong></td>
                                    <td><span class="imc__categoria imc__categoria--sm cat-<?= $c['chave'] ?>"><?= e($c['nome']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="planos" aria-labelledby="titulo-planos">
            <h2 id="titulo-planos">Plano indicado pelo perfil</h2>
            <?php if ($planos['faltando']): ?>
                <div class="alert alert--aviso" role="status">
                    O plano ainda não pode ser indicado:
                    <?= in_array('objetivo', $planos['faltando'], true) ? 'a pessoa não definiu o objetivo. ' : '' ?>
                    <?= in_array('maioridade', $planos['faltando'], true) ? 'as sugestões atuais são para maiores de 18 anos.' : '' ?>
                </div>
            <?php else: ?>
                <p class="planos__intro">
                    Objetivo <strong><?= e(ROTULOS_OBJETIVO[$paciente['objetivo']]) ?></strong>,
                    faixa de idade <strong><?= e(ROTULOS_FAIXA_ETARIA[$planos['treino']['faixa_etaria']]) ?></strong>
                    e gênero <strong><?= e(strtolower(ROTULOS_GENERO[$paciente['genero']])) ?></strong>.
                    É a sugestão automática do LifeMap, o mesmo que a pessoa vê no perfil dela.
                </p>
                <div class="planos__grade">
                    <?php foreach (['treino' => ['Treino indicado', 'tema-fisico'], 'dieta' => ['Dieta indicada', 'tema-ingesta']] as $tipo => [$titulo, $tema]): ?>
                        <?php $plano = $planos[$tipo]; ?>
                        <article class="card resultado <?= $tema ?>" aria-labelledby="plano-<?= $tipo ?>">
                            <h3 class="plano__titulo" id="plano-<?= $tipo ?>"><?= $titulo ?></h3>
                            <?php require __DIR__ . '/../partials/plano.php'; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php $modo = 'profissional'; require __DIR__ . '/../partials/avaliacao_resumo.php'; ?>

        <section class="card" aria-labelledby="titulo-humor">
            <h2 id="titulo-humor">Humor nos últimos <?= FICHA_DIAS_HUMOR ?> dias</h2>
            <?php if (!$comHumor): ?>
                <p>Esta pessoa não registrou o humor nesse período.</p>
            <?php else:
                $mediaNivel = (int) round($mediaHumor);
                $mediaInfo = humor_do_nivel($mediaNivel);
                ?>
                <p>
                    <strong><?= count($comHumor) ?></strong> registro<?= count($comHumor) === 1 ? '' : 's' ?> ·
                    média <strong><?= formatar_decimal($mediaHumor) ?></strong> de 5
                    (<?= e($mediaInfo['emoji']) ?> <?= e($mediaInfo['nome']) ?>)
                </p>
            <?php endif; ?>

            <ol class="humor-tira" aria-label="Humor dia a dia, do mais antigo ao mais recente">
                <?php foreach ($humor as $dia):
                    $info = $dia['nivel'] !== null ? humor_do_nivel($dia['nivel']) : null;
                    $rotuloData = (new DateTime($dia['data']))->format('d/m');
                    ?>
                    <li class="humor-dia<?= $info ? ' humor-dia--' . $dia['nivel'] : '' ?>"
                        title="<?= e($rotuloData . ($info ? ' · ' . $info['nome'] : ' · sem registro')) ?>"
                        aria-label="<?= e($rotuloData . ': ' . ($info ? $info['nome'] : 'sem registro')) ?>">
                        <span aria-hidden="true"><?= $info ? e($info['emoji']) : '·' ?></span>
                        <small aria-hidden="true"><?= (new DateTime($dia['data']))->format('j') ?></small>
                    </li>
                <?php endforeach; ?>
            </ol>

            <?php if ($anotacoes): ?>
                <h3 class="hist__titulo">Anotações recentes</h3>
                <ul class="anotacoes">
                    <?php foreach ($anotacoes as $dia): $info = humor_do_nivel($dia['nivel']); ?>
                        <li>
                            <strong><?= (new DateTime($dia['data']))->format('d/m') ?></strong>
                            <span aria-hidden="true"><?= e($info['emoji']) ?></span>
                            <span class="visually-hidden"><?= e($info['nome']) ?>:</span>
                            <?= e($dia['nota']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="card" aria-labelledby="titulo-rotina">
            <h2 id="titulo-rotina">Rotina desta semana</h2>
            <p class="nota">
                <?= $semana['inicio']->format('d/m') ?> a <?= $semana['fim']->format('d/m') ?> ·
                <?= $semana['eventos'] ?> compromisso<?= $semana['eventos'] === 1 ? '' : 's' ?> na agenda
            </p>
            <?php if ($semana['eventos'] === 0): ?>
                <p>Nenhum compromisso na agenda desta semana.</p>
            <?php else: ?>
                <ul class="barras">
                    <?php foreach ($semana['categorias'] as $c): ?>
                        <li class="barra barra--<?= e($c['chave']) ?>">
                            <span class="barra__nome"><?= e($c['nome']) ?></span>
                            <span class="barra__trilho" aria-hidden="true">
                                <span class="barra__valor" style="width: <?= $c['minutos'] > 0 ? max(2, round($c['minutos'] / $maiorTempo * 100)) : 0 ?>%"></span>
                            </span>
                            <span class="barra__tempo"><?= e(formatar_minutos($c['minutos'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php if ($ultimoImc): ?>
    <script type="application/json" id="ficha-config"><?= json_encode($configJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script src="<?= asset('js/grafico-imc.js') ?>"></script>
    <script src="<?= asset('js/ficha.js') ?>"></script>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
