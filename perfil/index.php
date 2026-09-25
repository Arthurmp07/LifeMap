<?php
require_once __DIR__ . '/../includes/bootstrap.php';

exigir_papel('usuario');

$usuario = usuario_atual();
$erros = flash_get('perfil_erros') ?? [];
$sucesso = flash_get('perfil_ok');
$antigo = flash_get('perfil_old');

// Depois de um erro, mostra o que a pessoa digitou; senão, o que está salvo.
$v = $antigo ?? [
    'nome' => $usuario['nome'],
    'genero' => $usuario['genero'],
    'data_nascimento' => $usuario['data_nascimento'],
    'telefone' => $usuario['telefone'],
    'altura' => $usuario['altura'] !== null ? formatar_decimal((float) $usuario['altura'], 2) : '',
    'objetivo' => $usuario['objetivo'] ?? '',
    'problema_saude' => $usuario['problema_saude'] ?? '',
];

// Registro de IMC mais recente (resumo ao lado) e treino/dieta indicados pelo perfil salvo.
$ultimoImc = ultimo_imc((int) $usuario['id']);
$planos = planos_do_perfil();
$avaliacoes = listar_avaliacoes((int) $usuario['id'], 2);

$pageTitle = 'Meu perfil';
$paginaAtiva = 'perfil';
$pageDescription = 'Seus dados pessoais e objetivo, usados para preencher os geradores de treino e dieta.';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container">
        <div class="page-head page-head--acoes">
            <div>
                <span class="eyebrow">Meu perfil</span>
                <h1>Seu perfil</h1>
                <p class="lead">Veja o treino e a dieta indicados para você e mantenha seus dados em dia. Eles também preenchem os geradores e o cálculo de IMC.</p>
            </div>
            <a class="btn btn--primary" href="<?= url('perfil/exportar_pdf.php') ?>" title="Baixa um PDF com os dados salvos do seu perfil, o treino e a dieta indicados">
                <ion-icon name="download-outline"></ion-icon> Exportar PDF
            </a>
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
        <?php if (!$usuario['cadastro_completo'] && !$erros): ?>
            <div class="alert alert--aviso" role="status">
                Complete seu perfil informando <strong>altura</strong> e <strong>objetivo</strong> para receber sugestões mais certeiras.
            </div>
        <?php endif; ?>

        <section class="planos" aria-labelledby="titulo-planos">
            <h2 id="titulo-planos">Seu plano indicado</h2>

            <?php if ($planos['faltando']): ?>
                <div class="alert alert--aviso" role="status">
                    <strong>Complete seu perfil para ver o treino e a dieta indicados.</strong>
                    <ul>
                        <?php if (in_array('objetivo', $planos['faltando'], true)): ?>
                            <li>Escolha seu <strong>objetivo</strong> em “Objetivo e saúde”, mais abaixo.</li>
                        <?php endif; ?>
                        <?php if (in_array('maioridade', $planos['faltando'], true)): ?>
                            <li>As sugestões atuais são para <strong>maiores de 18 anos</strong>. Confira sua data de nascimento.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php elseif ($planos['treino'] && $planos['dieta']): ?>
                <p class="planos__intro">
                    Indicados para o objetivo <strong><?= e(ROTULOS_OBJETIVO[$usuario['objetivo']]) ?></strong>,
                    faixa de idade <strong><?= e(ROTULOS_FAIXA_ETARIA[$planos['treino']['faixa_etaria']]) ?></strong>
                    e gênero <strong><?= e(strtolower(ROTULOS_GENERO[$usuario['genero']])) ?></strong>.
                    Eles mudam quando você atualiza o perfil.
                </p>

                <div class="planos__grade">
                    <?php foreach (['treino' => ['Treino indicado', 'tema-fisico', url('fisico/treino.php')],
                                    'dieta' => ['Dieta indicada', 'tema-ingesta', url('ingesta/dieta.php')]] as $tipo => [$titulo, $tema, $destino]): ?>
                        <?php $plano = $planos[$tipo]; ?>
                        <article class="card resultado <?= $tema ?>" aria-labelledby="plano-<?= $tipo ?>">
                            <h3 class="plano__titulo" id="plano-<?= $tipo ?>"><?= $titulo ?></h3>
                            <?php require __DIR__ . '/../partials/plano.php'; ?>
                            <p class="resultado__nota"><a href="<?= $destino ?>">Simular outra combinação →</a></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php $modo = 'proprio'; require __DIR__ . '/../partials/avaliacao_resumo.php'; ?>

        <div class="perfil">
            <form class="card" action="<?= url('perfil/processar_perfil.php') ?>" method="POST">
                <?= csrf_campo() ?>

                <h2>Dados pessoais</h2>

                <div class="field">
                    <label for="nome">Nome completo</label>
                    <input class="input" type="text" id="nome" name="nome" value="<?= e($v['nome']) ?>" maxlength="100" autocomplete="name" required>
                </div>

                <div class="field">
                    <label for="email">E-mail</label>
                    <input class="input" type="email" id="email" value="<?= e($usuario['email']) ?>" disabled>
                    <p class="field__hint">O e-mail é o seu login e não pode ser alterado por aqui.</p>
                </div>

                <fieldset class="fieldset">
                    <legend>Gênero</legend>
                    <div class="chip-group">
                        <?php foreach (ROTULOS_GENERO as $valor => $rotulo): ?>
                            <label class="chip">
                                <input type="radio" name="genero" value="<?= $valor ?>"<?= $v['genero'] === $valor ? ' checked' : '' ?> required>
                                <span><?= $rotulo ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="form-row">
                    <div class="field">
                        <label for="data_nascimento">Data de nascimento</label>
                        <input class="input" type="date" id="data_nascimento" name="data_nascimento" value="<?= e($v['data_nascimento']) ?>"
                               min="1900-01-01" max="<?= date('Y-m-d') ?>" autocomplete="bday" required>
                    </div>
                    <div class="field">
                        <label for="telefone">Telefone</label>
                        <input class="input" type="tel" id="telefone" name="telefone" value="<?= e($v['telefone']) ?>"
                               placeholder="(11) 91234-5678" inputmode="numeric" maxlength="15" autocomplete="tel"
                               data-mascara="telefone" required>
                    </div>
                </div>

                <h2 class="perfil__subtitulo">Objetivo e saúde</h2>

                <div class="field">
                    <label for="altura">Altura (m) <span class="field__optional">(opcional)</span></label>
                    <input class="input" type="text" id="altura" name="altura" value="<?= e($v['altura']) ?>"
                           inputmode="decimal" placeholder="Ex.: 1,75" autocomplete="off">
                </div>

                <fieldset class="fieldset">
                    <legend>Objetivo <span class="field__optional">(opcional)</span></legend>
                    <div class="chip-group">
                        <label class="chip">
                            <input type="radio" name="objetivo" value=""<?= $v['objetivo'] === '' ? ' checked' : '' ?>>
                            <span>Não definido</span>
                        </label>
                        <?php foreach (ROTULOS_OBJETIVO as $valor => $rotulo): ?>
                            <label class="chip">
                                <input type="radio" name="objetivo" value="<?= $valor ?>"<?= $v['objetivo'] === $valor ? ' checked' : '' ?>>
                                <span><?= $rotulo ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="field">
                    <label for="problema_saude">Problema de saúde <span class="field__optional">(opcional)</span></label>
                    <input class="input" type="text" id="problema_saude" name="problema_saude" value="<?= e($v['problema_saude']) ?>"
                           maxlength="255" placeholder="Ex.: dor no joelho, hipertensão, diabetes…">
                </div>

                <button type="submit" class="btn btn--primary btn--lg">Salvar alterações</button>
            </form>

            <aside class="perfil__lateral">
                <section class="card" aria-labelledby="titulo-imc">
                    <h2 id="titulo-imc">Seu IMC</h2>
                    <?php if ($ultimoImc):
                        $imc = (float) $ultimoImc['imc'];
                        $categoria = imc_categoria($imc);
                        $data = new DateTime($ultimoImc['criado_em']);
                        ?>
                        <div class="stat">
                            <span class="stat__label">Registro mais recente</span>
                            <span class="stat__value"><?= formatar_decimal($imc) ?></span>
                            <span class="imc__categoria cat-<?= $categoria['chave'] ?>"><?= e($categoria['nome']) ?></span>
                            <span class="stat__delta"><?= formatar_peso((float) $ultimoImc['peso']) ?> kg · em <?= $data->format('d/m/Y') ?></span>
                        </div>
                        <a class="btn btn--ghost btn--block" href="<?= url('fisico/imc.php') ?>#historico">Ver evolução</a>
                    <?php else: ?>
                        <p>Você ainda não salvou nenhum IMC.</p>
                        <a class="btn btn--primary btn--block" href="<?= url('fisico/imc.php') ?>">Calcular meu IMC</a>
                    <?php endif; ?>
                </section>

                <section class="card" aria-labelledby="titulo-seguranca">
                    <h2 id="titulo-seguranca">Segurança</h2>
                    <p>Troque a senha da sua conta quando quiser.</p>
                    <a class="btn btn--ghost btn--block" href="<?= url('auth/alterar_senha.php') ?>">Alterar senha</a>
                </section>

            </aside>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
