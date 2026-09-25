<?php
require_once __DIR__ . '/../includes/bootstrap.php';

exigir_papel('usuario');

// Dados que o assets/js/rotina.js lê.
$configJs = [
    'categorias' => CATEGORIAS_EVENTO,
    'humores'    => NIVEIS_HUMOR,
    'api'        => url('rotina/api.php'),
    'hoje'       => date('Y-m-d'),
    'limites'    => ['titulo' => EVENTO_TITULO_MAX, 'observacao' => EVENTO_OBSERVACAO_MAX, 'nota' => HUMOR_NOTA_MAX],
];

$pageTitle = 'Rotina';
$paginaAtiva = 'rotina';
$pageDescription = 'Organize seu dia: estudo, trabalho, treino, refeições, sono e lazer, e registre como você está se sentindo.';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="conteudo">
    <div class="container">
        <div class="page-head">
            <span class="eyebrow">Rotina</span>
            <h1>Organize seu dia</h1>
            <p class="lead">Reserve horários para estudo, trabalho, treino, refeições, sono e lazer, e registre como você está se sentindo em cada dia.</p>
        </div>

        <div class="rotina">
            <!-- Calendário -->
            <section class="card cal" aria-labelledby="cal-titulo">
                <div class="cal__topo">
                    <h2 id="cal-titulo" class="cal__mes" aria-live="polite">Carregando…</h2>
                    <div class="cal__nav">
                        <button type="button" class="btn btn--ghost btn--sm btn--icone" id="cal-anterior" aria-label="Mês anterior">‹</button>
                        <button type="button" class="btn btn--ghost btn--sm" id="cal-hoje">Hoje</button>
                        <button type="button" class="btn btn--ghost btn--sm btn--icone" id="cal-proximo" aria-label="Próximo mês">›</button>
                    </div>
                </div>

                <table class="cal__tabela" aria-labelledby="cal-titulo">
                    <thead>
                        <tr>
                            <?php foreach (['Dom' => 'domingo', 'Seg' => 'segunda', 'Ter' => 'terça', 'Qua' => 'quarta', 'Qui' => 'quinta', 'Sex' => 'sexta', 'Sáb' => 'sábado'] as $abrev => $nome): ?>
                                <th scope="col"><abbr title="<?= $nome ?>"><?= $abrev ?></abbr></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody id="cal-corpo"></tbody>
                </table>

                <ul class="cal__legenda" aria-label="Categorias">
                    <?php foreach (CATEGORIAS_EVENTO as $c): ?>
                        <li class="ev-<?= $c['chave'] ?>"><span class="chave"></span><?= e($c['nome']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <!-- Humor do dia selecionado -->
            <section class="card humor" aria-labelledby="humor-titulo">
                <h2 id="humor-titulo">Como você está se sentindo?</h2>
                <p class="humor__data" id="humor-data"></p>

                <div class="humor__opcoes" role="radiogroup" aria-labelledby="humor-titulo" id="humor-opcoes">
                    <?php foreach (NIVEIS_HUMOR as $h): ?>
                        <label class="humor__opcao">
                            <input type="radio" name="humor" value="<?= $h['nivel'] ?>">
                            <span class="humor__emoji" aria-hidden="true"><?= $h['emoji'] ?></span>
                            <span class="humor__nome"><?= e($h['nome']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="humor__nota" id="humor-nota-area" hidden>
                    <label class="visually-hidden" for="humor-nota">Anotação sobre o seu dia (opcional)</label>
                    <input class="input" type="text" id="humor-nota" maxlength="<?= HUMOR_NOTA_MAX ?>"
                           placeholder="Quer anotar algo sobre o dia? (opcional)" autocomplete="off">
                    <button type="button" class="btn btn--ghost btn--sm" id="humor-salvar-nota">Salvar anotação</button>
                </div>

                <p class="humor__estado" id="humor-estado" aria-live="polite"></p>
            </section>

            <!-- Dia selecionado -->
            <section class="card dia" aria-labelledby="dia-titulo">
                <div class="dia__topo">
                    <div>
                        <h2 id="dia-titulo">Carregando…</h2>
                        <p class="dia__sub" id="dia-sub"></p>
                    </div>
                    <button type="button" class="btn btn--primary btn--sm" id="novo-evento">+ Novo evento</button>
                </div>

                <div class="resumo" id="dia-resumo"></div>

                <div class="agenda" id="dia-agenda" tabindex="-1"></div>
            </section>
        </div>
    </div>
</main>

<!-- Formulário de evento -->
<dialog class="dialogo" id="evento-dialogo" aria-labelledby="evento-titulo-dialogo">
    <form id="evento-form" novalidate>
        <div class="dialogo__topo">
            <h2 id="evento-titulo-dialogo">Novo evento</h2>
            <button type="button" class="btn btn--ghost btn--sm btn--icone" data-fechar aria-label="Fechar">✕</button>
        </div>

        <div class="dialogo__corpo">
            <div class="alert alert--erro" id="evento-erro" role="alert" hidden></div>
            <div class="alert alert--aviso" id="evento-conflito" role="alert" hidden>
                <strong id="evento-conflito-msg"></strong>
                <ul id="evento-conflito-lista"></ul>
                <button type="button" class="btn btn--ghost btn--sm" id="evento-forcar">Salvar mesmo assim</button>
            </div>

            <fieldset class="fieldset">
                <legend>Categoria</legend>
                <div class="chip-group chip-group--categorias">
                    <?php foreach (CATEGORIAS_EVENTO as $c): ?>
                        <label class="chip chip--categoria ev-<?= $c['chave'] ?>">
                            <input type="radio" name="categoria" value="<?= $c['chave'] ?>">
                            <span><span class="chave"></span><?= e($c['nome']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <div class="field">
                <label for="ev-titulo">Título</label>
                <input class="input" type="text" id="ev-titulo" name="titulo" maxlength="<?= EVENTO_TITULO_MAX ?>" autocomplete="off" required>
            </div>

            <div class="form-row form-row--3">
                <div class="field">
                    <label for="ev-data">Data</label>
                    <input class="input" type="date" id="ev-data" name="data" required>
                </div>
                <div class="field">
                    <label for="ev-inicio">Início</label>
                    <input class="input" type="time" id="ev-inicio" name="hora_inicio" required>
                </div>
                <div class="field">
                    <label for="ev-fim">Fim</label>
                    <input class="input" type="time" id="ev-fim" name="hora_fim" required>
                </div>
            </div>
            <p class="field__hint" id="ev-duracao" aria-live="polite"></p>

            <div class="field">
                <label for="ev-obs">Observação <span class="field__optional">(opcional)</span></label>
                <input class="input" type="text" id="ev-obs" name="observacao" maxlength="<?= EVENTO_OBSERVACAO_MAX ?>" autocomplete="off">
            </div>

            <div class="form-row" id="ev-repetir-area">
                <div class="field">
                    <label for="ev-repetir">Repetir</label>
                    <select class="input" id="ev-repetir" name="repetir">
                        <option value="nenhuma">Não repete</option>
                        <option value="diaria">Todos os dias</option>
                        <option value="uteis">Dias úteis (seg a sex)</option>
                        <option value="semanal">Toda semana</option>
                    </select>
                </div>
                <div class="field" id="ev-ate-campo" hidden>
                    <label for="ev-ate">Até</label>
                    <input class="input" type="date" id="ev-ate" name="ate">
                </div>
            </div>
            <p class="field__hint" id="ev-serie-aviso" hidden>Este evento faz parte de uma série. As alterações valem só para ele.</p>
        </div>

        <div class="dialogo__rodape">
            <div class="dialogo__excluir" id="evento-excluir-area" hidden>
                <button type="button" class="btn btn--ghost btn--sm btn--perigo" id="evento-excluir">Excluir</button>
                <button type="button" class="btn btn--ghost btn--sm btn--perigo" id="evento-excluir-serie" hidden>Excluir toda a série</button>
            </div>
            <div class="dialogo__acoes">
                <button type="button" class="btn btn--ghost" data-fechar>Cancelar</button>
                <button type="submit" class="btn btn--primary" id="evento-salvar">Salvar</button>
            </div>
        </div>
    </form>
</dialog>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script type="application/json" id="rotina-config"><?= json_encode($configJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="<?= asset('js/rotina.js') ?>"></script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
