<?php
/** @var array $v @var ?int $id @var array $erros @var array $pessoas @var array $setores @var array $projetos */
$val = fn(string $k) => $v[$k] ?? '';
$acao = $id ? "/reunioes/$id" : '/reunioes';
$marcas = function (string $nome, array $itens, array $sel, bool $cor): string {
    $h = '<div class="grade-marcas">';
    foreach ($itens as $i) {
        $c = $cor ? ' style="--c:' . e(cor_valida($i['color'])) . '"' : '';
        $h .= '<label class="marca"' . $c . '><input type="checkbox" name="' . e($nome) . '[]" value="' . (int) $i['id'] . '"' . (in_array((int) $i['id'], $sel, true) ? ' checked' : '') . '><span>' . e($i['name']) . '</span></label>';
    }
    return $h . '</div>';
};
?>
<div class="cabeca-pagina"><a class="voltar" href="/reunioes">← Voltar às reuniões</a></div>
<?php foreach ($erros as $er): ?><div class="aviso erro" role="alert"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" action="<?= e($acao) ?>" class="formulario cartao ficha">
  <?= Csrf::campo() ?>
  <label class="campo-titulo">Nome da reunião
    <input type="text" name="title" value="<?= e($val('title')) ?>" required maxlength="255" autofocus placeholder="Ex.: Reunião geral de outubro">
  </label>
  <div class="linha tres">
    <label>Data da reunião<input type="date" name="meeting_date" value="<?= e($val('meeting_date')) ?>"></label>
    <label>Tipo de reunião
      <select name="kind"><?php foreach (Reunioes::TIPOS as $t): ?><option<?= $val('kind') === $t ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select>
    </label>
    <label>Projeto
      <select name="project_id"><option value="">—</option>
        <?php foreach ($projetos as $p): ?><option value="<?= (int) $p['id'] ?>"<?= (int) $val('project_id') === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
  </div>
  <div class="linha dois">
    <label>Responsável da ata
      <select name="minutes_by"><option value="">—</option>
        <?php foreach ($pessoas as $p): ?><option value="<?= (int) $p['id'] ?>"<?= (int) $val('minutes_by') === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Link da ata <small>(Google Docs, Drive…)</small><input type="url" name="minutes_url" value="<?= e($val('minutes_url')) ?>" maxlength="600" placeholder="https://…"></label>
  </div>
  <fieldset><legend>Setores envolvidos</legend><?= $marcas('setores', $setores, $v['_setores'] ?? [], true) ?></fieldset>
  <fieldset><legend>Participantes <small>(quem for marcado recebe uma notificação)</small></legend><?= $marcas('participantes', $pessoas, $v['_participantes'] ?? [], false) ?></fieldset>
  <label>Anotações / resumo <small>(opcional)</small><textarea name="notes" rows="10" maxlength="200000"><?= e($val('notes')) ?></textarea></label>
  <div class="acoes">
    <button class="btn primario" type="submit"><?= $id ? 'Salvar alterações' : 'Registrar reunião' ?></button>
    <a class="btn texto" href="/reunioes">Cancelar</a>
  </div>
</form>
<?php if ($id && Perm::admin(Auth::usuario())): ?>
  <form method="post" action="/reunioes/<?= (int) $id ?>/excluir" class="excluir" data-confirmar="Excluir esta reunião para sempre?">
    <?= Csrf::campo() ?><button class="btn perigo texto" type="submit">Excluir reunião</button>
  </form>
<?php endif; ?>
