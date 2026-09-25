<?php
// Painel do administrador: cadastro e controle das contas de profissional.
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/profissionais.php';

exigir_papel('admin');

$erros = flash_get('painel_erros') ?? [];
$sucesso = flash_get('painel_ok');
$senhaNova = flash_get('admin_senha');
$antigo = flash_get('admin_old') ?? [];
$profissionais = listar_profissionais();

if ($senhaNova) {
    header('Cache-Control: no-store');   // a senha aparece só uma vez: nada de cópia em cache
}

$ativos = count(array_filter($profissionais, fn($p) => $p['ativo']));

$pageTitle = 'Profissionais';
$paginaAtiva = 'admin';
$pageDescription = 'Cadastre e gerencie as contas de profissionais do LifeMap.';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container painel">
        <div class="page-head">
            <span class="eyebrow">Administração</span>
            <h1>Profissionais</h1>
            <p class="lead">Só o administrador cria contas de profissional. Cada profissional entra com uma senha provisória, que ele troca no primeiro acesso.</p>
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

        <?php if ($senhaNova): ?>
            <section class="card senha-nova" aria-labelledby="titulo-senha-nova">
                <h2 id="titulo-senha-nova">Senha provisória de <?= e($senhaNova['nome']) ?></h2>
                <p>
                    Conta <?= $senhaNova['quando'] === 'cadastrado' ? 'cadastrada' : 'com senha redefinida' ?> para <strong><?= e($senhaNova['email']) ?></strong>.
                    <strong>Copie a senha agora: ela não será mostrada de novo.</strong>
                    Entregue por um canal seguro; no primeiro acesso, a pessoa será obrigada a criar uma senha nova.
                </p>
                <div class="senha-nova__linha">
                    <code class="senha-nova__valor" id="senha-provisoria"><?= e($senhaNova['senha']) ?></code>
                    <button type="button" class="btn btn--ghost btn--sm" data-copiar="#senha-provisoria">
                        <ion-icon name="copy-outline"></ion-icon> Copiar
                    </button>
                </div>
            </section>
        <?php endif; ?>

        <section class="card" aria-labelledby="titulo-cadastro">
            <h2 id="titulo-cadastro">Cadastrar profissional</h2>
            <form action="<?= url('admin/processar.php') ?>" method="POST" autocomplete="off">
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="criar">

                <div class="form-row">
                    <div class="field">
                        <label for="nome">Nome completo</label>
                        <input class="input" type="text" id="nome" name="nome" value="<?= e($antigo['nome'] ?? '') ?>" maxlength="100" required>
                    </div>
                    <div class="field">
                        <label for="email">E-mail (será o login)</label>
                        <input class="input" type="email" id="email" name="email" value="<?= e($antigo['email'] ?? '') ?>" maxlength="100" required>
                    </div>
                </div>

                <div class="form-row form-row--3">
                    <div class="field">
                        <label for="especialidade">Especialidade</label>
                        <input class="input" type="text" id="especialidade" name="especialidade" value="<?= e($antigo['especialidade'] ?? '') ?>"
                               list="especialidades" maxlength="80" placeholder="Ex.: Nutricionista" required>
                        <datalist id="especialidades">
                            <?php foreach (ESPECIALIDADES_SUGERIDAS as $sugestao): ?>
                                <option value="<?= e($sugestao) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="field">
                        <label for="registro">Registro profissional</label>
                        <input class="input" type="text" id="registro" name="registro" value="<?= e($antigo['registro'] ?? '') ?>"
                               maxlength="40" placeholder="Ex.: CRN-3 12345" required>
                    </div>
                    <div class="field">
                        <label for="telefone">Telefone</label>
                        <input class="input" type="tel" id="telefone" name="telefone" value="<?= e($antigo['telefone'] ?? '') ?>"
                               placeholder="(11) 91234-5678" inputmode="numeric" maxlength="15" data-mascara="telefone" required>
                    </div>
                </div>

                <button type="submit" class="btn btn--primary">Cadastrar e gerar senha provisória</button>
            </form>
        </section>

        <section class="card" aria-labelledby="titulo-lista">
            <h2 id="titulo-lista">Profissionais cadastrados <span class="nota">(<?= $ativos ?> ativo<?= $ativos === 1 ? '' : 's' ?> de <?= count($profissionais) ?>)</span></h2>

            <?php if (!$profissionais): ?>
                <p>Nenhum profissional cadastrado ainda. Use o formulário acima para cadastrar o primeiro.</p>
            <?php else: ?>
                <div class="tabela-rolagem">
                    <table class="tabela tabela--admin">
                        <thead>
                            <tr>
                                <th scope="col">Profissional</th>
                                <th scope="col">Especialidade</th>
                                <th scope="col">Atendimentos</th>
                                <th scope="col">Situação</th>
                                <th scope="col"><span class="visually-hidden">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($profissionais as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($p['nome']) ?></strong><br>
                                        <span class="nota"><?= e($p['email']) ?> · <?= e($p['telefone']) ?></span>
                                    </td>
                                    <td><?= e($p['especialidade']) ?><br><span class="nota"><?= e($p['registro']) ?></span></td>
                                    <td><?= (int) $p['pacientes'] ?> em andamento</td>
                                    <td>
                                        <?php if ($p['ativo']): ?>
                                            <span class="selo selo--ok">Ativo</span>
                                        <?php else: ?>
                                            <span class="selo selo--info">Desativado</span>
                                        <?php endif; ?>
                                        <?php if ($p['trocar_senha']): ?>
                                            <br><span class="nota">Ainda não trocou a senha provisória</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="acoes-linha">
                                            <form action="<?= url('admin/processar.php') ?>" method="POST"
                                                  data-confirmar="Gerar uma nova senha provisória para <?= e($p['nome']) ?>? A senha atual deixa de valer.">
                                                <?= csrf_campo() ?>
                                                <input type="hidden" name="acao" value="nova_senha">
                                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                                <button type="submit" class="btn btn--ghost btn--sm">Nova senha</button>
                                            </form>
                                            <form action="<?= url('admin/processar.php') ?>" method="POST"
                                                  <?php if ($p['ativo']): ?>data-confirmar="Desativar <?= e($p['nome']) ?>? Ele não poderá mais entrar e os atendimentos em andamento serão encerrados."<?php endif; ?>>
                                                <?= csrf_campo() ?>
                                                <input type="hidden" name="acao" value="<?= $p['ativo'] ? 'desativar' : 'reativar' ?>">
                                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                                <button type="submit" class="btn btn--ghost btn--sm<?= $p['ativo'] ? ' btn--perigo' : '' ?>"><?= $p['ativo'] ? 'Desativar' : 'Reativar' ?></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
