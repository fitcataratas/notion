<?php
/**
 * @var array|null $t tarefa  @var int|null $id  @var array $erros  @var bool $pode_editar
 * @var array $setores @var array $projetos @var array $pessoas  @var string $volta
 */
$usuario = Auth::usuario();
$v = fn(string $k) => $t[$k] ?? '';
$ids = fn(string $k) => array_map(fn($x) => (int) $x['id'], $t[$k] ?? []);
$marca = function (string $nome, array $itens, string $chave, bool $comCor = true) use ($ids): string {
    $sel = $ids($chave);
    $h = '<div class="grade-marcas">';
    foreach ($itens as $i) {
        $ok = in_array((int) $i['id'], $sel, true) ? ' checked' : '';
        $cor = $comCor && isset($i['color']) ? ' style="--c:' . e(cor_valida($i['color'])) . '"' : '';
        $h .= '<label class="marca"' . $cor . '><input type="checkbox" name="' . e($nome) . '[]" value="' . (int) $i['id'] . '"' . $ok . '><span>' . e($i['name']) . '</span></label>';
    }
    return $h . '</div>';
};
$opcoes = function (array $lista, ?string $atual, string $vazio = '—'): string {
    $h = '<option value="">' . e($vazio) . '</option>';
    foreach ($lista as $o) {
        $h .= '<option' . ($o === $atual ? ' selected' : '') . '>' . e($o) . '</option>';
    }
    return $h;
};
$temMkt = false;
foreach (['mkt_stage', 'material_type', 'requested_deadline', 'publish_date', 'piece_goal', 'target_audience', 'format_specs', 'references_text', 'materials_folder', 'requester_notes', 'content'] as $k) {
    if ($v($k) !== '' && $v($k) !== null) {
        $temMkt = true;
    }
}
if ($ids('solicitantes') || $ids('aprovadores') || $ids('setores_solicitantes')) {
    $temMkt = true;
}
$acao = $id ? "/tarefas/$id" : '/tarefas';
$comentarios ??= [];
$historico ??= [];
?>
<div class="cabeca-pagina">
  <a class="voltar" href="<?= e($volta) ?>">← Voltar</a>
</div>

<?php foreach ($erros as $er): ?><div class="aviso erro" role="alert"><?= e($er) ?></div><?php endforeach; ?>

<div class="duas-colunas<?= $id ? ' com-lateral' : '' ?>">
<div>
<?php if ($pode_editar): ?>
<form method="post" action="<?= e($acao) ?>" class="formulario cartao ficha">
  <?= Csrf::campo() ?>
  <input type="hidden" name="volta" value="<?= e($volta) ?>">

  <label class="campo-titulo">Atividade
    <input type="text" name="title" value="<?= e($v('title')) ?>" required maxlength="255" autofocus placeholder="O que precisa ser feito?">
  </label>

  <div class="linha tres">
    <label>Status<select name="status"><?= $opcoes(Config::STATUS, $v('status') ?: 'A fazer', 'A fazer') ?></select></label>
    <label>Prioridade<select name="priority"><?= $opcoes(Config::PRIORIDADES, $v('priority')) ?></select></label>
    <label>Tipo<select name="type"><?= $opcoes(Config::TIPOS, $v('type')) ?></select></label>
  </div>

  <div class="linha tres">
    <label>Data<input type="date" name="start_date" value="<?= e($v('start_date')) ?>"></label>
    <label>Hora (opcional)<input type="time" name="start_time" value="<?= e(hora_curta($v('start_time'))) ?>"></label>
    <label>Data final (se durar mais de um dia)<input type="date" name="end_date" value="<?= e($v('end_date')) ?>"></label>
  </div>

  <fieldset><legend>Responsável</legend><?= $marca('responsaveis', $pessoas, 'responsaveis', false) ?></fieldset>
  <fieldset><legend>Setor</legend><?= $marca('setores', $setores, 'setores') ?></fieldset>
  <fieldset><legend>Projeto</legend><?= $marca('projetos', $projetos, 'projetos') ?></fieldset>

  <label>Briefing <small>(explique a demanda)</small>
    <textarea name="briefing" rows="4" maxlength="20000"><?= e($v('briefing')) ?></textarea>
  </label>

  <details class="bloco-mkt"<?= $temMkt ? ' open' : '' ?>>
    <summary>Solicitação de Marketing e Design</summary>
    <div class="linha dois">
      <label>Etapa<select name="mkt_stage"><?= $opcoes(Config::ETAPAS_MKT, $v('mkt_stage')) ?></select></label>
      <label>Tipo de material<select name="material_type"><?= $opcoes(Config::TIPOS_MATERIAL, $v('material_type')) ?></select></label>
    </div>
    <div class="linha dois">
      <label>Prazo solicitado<input type="date" name="requested_deadline" value="<?= e($v('requested_deadline')) ?>"></label>
      <label>Data de publicação/impressão<input type="date" name="publish_date" value="<?= e($v('publish_date')) ?>"></label>
    </div>
    <fieldset><legend>Solicitante</legend><?= $marca('solicitantes', $pessoas, 'solicitantes', false) ?></fieldset>
    <fieldset><legend>Setor solicitante</legend><?= $marca('setores_solicitantes', $setores, 'setores_solicitantes') ?></fieldset>
    <fieldset><legend>Responsável pela aprovação</legend><?= $marca('aprovadores', $pessoas, 'aprovadores', false) ?></fieldset>
    <label>Objetivo da peça<textarea name="piece_goal" rows="2" maxlength="5000"><?= e($v('piece_goal')) ?></textarea></label>
    <label>Público alvo<textarea name="target_audience" rows="2" maxlength="5000"><?= e($v('target_audience')) ?></textarea></label>
    <label>Formato e especificações<textarea name="format_specs" rows="2" maxlength="5000"><?= e($v('format_specs')) ?></textarea></label>
    <label>Conteúdo (texto da peça)<textarea name="content" rows="4" maxlength="100000"><?= e($v('content')) ?></textarea></label>
    <label>Referências<textarea name="references_text" rows="2" maxlength="10000"><?= e($v('references_text')) ?></textarea></label>
    <label>Pasta de materiais <small>(link)</small><input type="text" name="materials_folder" value="<?= e($v('materials_folder')) ?>" maxlength="2000"></label>
    <label>Observações do solicitante<textarea name="requester_notes" rows="2" maxlength="10000"><?= e($v('requester_notes')) ?></textarea></label>
  </details>

  <label>Anotações da página <small>(texto livre, links, conversas)</small>
    <textarea name="body" rows="6" maxlength="200000"><?= e($v('body')) ?></textarea>
  </label>

  <div class="acoes">
    <button class="btn primario" type="submit"><?= $id ? 'Salvar alterações' : 'Criar tarefa' ?></button>
    <a class="btn texto" href="<?= e($volta) ?>">Cancelar</a>
  </div>
</form>

<?php if ($id && Perm::admin($usuario)): ?>
  <form method="post" action="/tarefas/<?= (int) $id ?>/excluir" class="excluir" data-confirmar="Excluir esta tarefa para sempre? Essa ação não pode ser desfeita. Se só quiser tirar do quadro, use o status Cancelado.">
    <?= Csrf::campo() ?>
    <button class="btn perigo texto" type="submit">Excluir tarefa</button>
  </form>
<?php endif; ?>

<?php else: /* ----------- visão somente leitura (usuário externo) ----------- */ ?>
<section class="cartao ficha leitura">
  <h2><?= e($v('title')) ?></h2>
  <div class="chips">
    <?= chip_status($v('status')) ?> <?= chip_prioridade($v('priority')) ?>
    <?php foreach ($t['projetos'] as $x): ?><?= chip($x['name'], $x['color']) ?><?php endforeach; ?>
    <?php foreach ($t['setores'] as $x): ?><?= chip($x['name'], $x['color']) ?><?php endforeach; ?>
  </div>
<?php if ($pode_status ?? true): ?>
  <form method="post" action="/tarefas/<?= (int) $id ?>/status" class="linha-status">
    <?= Csrf::campo() ?>
    <input type="hidden" name="volta" value="/tarefas/<?= (int) $id ?>">
    <label>Mudar status
      <select name="status"><?= $opcoes(Config::STATUS, $v('status'), 'A fazer') ?></select>
    </label>
    <button class="btn primario" type="submit">Atualizar</button>
  </form>
<?php endif; ?>
  <dl class="definicoes">
    <?php
    $linhas = [
        'Data' => data_tarefa($t) ? data_br($v('start_date')) . ($v('start_time') ? ' ' . hora_curta($v('start_time')) : '') . ($v('end_date') ? ' até ' . data_br($v('end_date')) : '') : '',
        'Tipo' => $v('type'), 'Tipo de material' => $v('material_type'), 'Etapa' => $v('mkt_stage'),
        'Prazo solicitado' => data_br($v('requested_deadline')), 'Publicação/impressão' => data_br($v('publish_date')),
    ];
    foreach ($linhas as $rot => $val): if ($val): ?><dt><?= e($rot) ?></dt><dd><?= e($val) ?></dd><?php endif; endforeach;
    foreach (['responsaveis' => 'Responsável', 'solicitantes' => 'Solicitante', 'aprovadores' => 'Aprovação'] as $k => $rot):
        if ($t[$k]): ?><dt><?= $rot ?></dt><dd><?= e(implode(', ', array_column($t[$k], 'name'))) ?></dd><?php endif; endforeach;
    foreach (['briefing' => 'Briefing', 'piece_goal' => 'Objetivo da peça', 'target_audience' => 'Público alvo', 'format_specs' => 'Formato e especificações', 'content' => 'Conteúdo', 'references_text' => 'Referências', 'materials_folder' => 'Pasta de materiais', 'requester_notes' => 'Observações', 'body' => 'Anotações'] as $k => $rot):
        if ($v($k)): ?><dt><?= $rot ?></dt><dd><?= texto_html($v($k)) ?></dd><?php endif; endforeach; ?>
  </dl>
</section>
<?php endif; ?>
</div>

<?php if ($id): ?>
<aside class="lateral-ficha">
  <section class="cartao" id="comentarios">
    <h3>Comentários</h3>
    <?php foreach ($comentarios as $c): ?>
      <article class="comentario">
        <header><strong><?= e($c['name'] ?? 'Usuário removido') ?></strong> <time><?= e(datahora_br($c['created_at'])) ?></time></header>
        <p><?= texto_html($c['body']) ?></p>
      </article>
    <?php endforeach; ?>
    <?php if (!$comentarios): ?><p class="suave">Nenhum comentário ainda.</p><?php endif; ?>
    <form method="post" action="/tarefas/<?= (int) $id ?>/comentarios" class="formulario">
      <?= Csrf::campo() ?>
      <label class="sr" for="novo-com">Novo comentário</label>
      <textarea id="novo-com" name="texto" rows="3" maxlength="5000" placeholder="Escreva um comentário…" required></textarea>
      <div class="acoes"><button class="btn" type="submit">Comentar</button></div>
    </form>
  </section>

  <?php if ($pode_editar): ?>
  <section class="cartao">
    <h3>Histórico</h3>
    <?php foreach ($historico as $h): $det = $h['details'] ? json_decode($h['details'], true) : null; ?>
      <details class="hist">
        <summary><?= e($h['summary']) ?><small><?= e($h['user_name'] ?? '') ?> · <?= e(datahora_br($h['created_at'])) ?></small></summary>
        <?php if (is_array($det)): ?><ul>
          <?php foreach ($det as $campo => $par): ?><li><strong><?= e($campo) ?>:</strong> <?= e($par[0] ?: '—') ?> → <?= e($par[1] ?: '—') ?></li><?php endforeach; ?>
        </ul><?php endif; ?>
      </details>
    <?php endforeach; ?>
    <?php if (!$historico): ?><p class="suave">Sem alterações registradas.</p><?php endif; ?>
  </section>
  <?php endif; ?>
</aside>
<?php endif; ?>
</div>
