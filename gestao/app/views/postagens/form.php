<?php
/** @var array $v @var ?int $id @var array $erros @var array $listas @var array $pessoas @var string $volta @var ?array $ideia */
$val = fn(string $k) => $v[$k] ?? '';
$opts = function (string $campo, array $itens) use ($val): string {
    $h = '<option value="">—</option>';
    foreach ($itens as $i) {
        $h .= '<option value="' . (int) $i['id'] . '"' . ((int) $val($campo) === (int) $i['id'] ? ' selected' : '') . '>' . e($i['name']) . '</option>';
    }
    return $h;
};
$marcas = function (string $nome, array $itens, array $sel, bool $cor) : string {
    $h = '<div class="grade-marcas">';
    foreach ($itens as $i) {
        $c = $cor ? ' style="--c:' . e(cor_valida($i['color'])) . '"' : '';
        $h .= '<label class="marca"' . $c . '><input type="checkbox" name="' . e($nome) . '[]" value="' . (int) $i['id'] . '"' . (in_array((int) $i['id'], $sel, true) ? ' checked' : '') . '><span>' . e($i['name']) . '</span></label>';
    }
    return $h . '</div>';
};
$acao = $id ? "/postagens/$id" : '/postagens';
?>
<div class="cabeca-pagina"><a class="voltar" href="<?= e($volta) ?>">← Voltar ao cronograma</a></div>
<?php foreach ($erros as $er): ?><div class="aviso erro" role="alert"><?= e($er) ?></div><?php endforeach; ?>
<?php if ($ideia): ?><div class="aviso">Esta postagem nasce da ideia “<?= e($ideia['title']) ?>”. Ao salvar, a ideia fica marcada como usada.</div><?php endif; ?>

<form method="post" action="<?= e($acao) ?>" class="formulario cartao ficha">
  <?= Csrf::campo() ?>
  <input type="hidden" name="volta" value="<?= e($volta) ?>">
  <?php if ($ideia): ?><input type="hidden" name="ideia" value="<?= (int) $ideia['id'] ?>"><?php endif; ?>

  <label class="campo-titulo">Título
    <input type="text" name="title" value="<?= e($val('title')) ?>" required maxlength="255" autofocus placeholder="Nome da postagem">
  </label>

  <div class="linha dois">
    <label>Data de Postagem<input type="date" name="post_date" value="<?= e($val('post_date')) ?>"></label>
    <label>Situação do post<select name="status_id"><?= $opts('status_id', $listas['situacao']) ?></select></label>
  </div>
  <div class="linha dois">
    <label>Projeto<select name="project_id"><?= $opts('project_id', $listas['projeto']) ?></select></label>
    <label>Tipo<select name="type_id"><?= $opts('type_id', $listas['tipo']) ?></select></label>
  </div>
  <label>Objetivo <small>(resumido)</small><input type="text" name="objective" value="<?= e($val('objective')) ?>" maxlength="255"></label>

  <fieldset><legend>Formato <small>(pode marcar mais de um)</small></legend><?= $marcas('formatos', $listas['formato'], $v['_formatos'] ?? [], true) ?></fieldset>
  <fieldset><legend>Plataforma <small>(pode marcar mais de uma)</small></legend><?= $marcas('plataformas', $listas['plataforma'], $v['_plataformas'] ?? [], true) ?></fieldset>
  <fieldset><legend>Profissional</legend><?= $marcas('pessoas', $pessoas, $v['_pessoas'] ?? [], false) ?></fieldset>

  <label>Conteúdo <small>(texto, chamada ou informação principal)</small>
    <textarea name="content" rows="8" maxlength="100000"><?= e($val('content')) ?></textarea>
  </label>

  <div class="acoes">
    <button class="btn primario" type="submit"><?= $id ? 'Salvar alterações' : 'Criar postagem' ?></button>
    <a class="btn texto" href="<?= e($volta) ?>">Cancelar</a>
  </div>
</form>

<?php if ($id && Perm::admin(Auth::usuario())): ?>
  <form method="post" action="/postagens/<?= (int) $id ?>/excluir" class="excluir" data-confirmar="Excluir esta postagem para sempre?">
    <?= Csrf::campo() ?>
    <button class="btn perigo texto" type="submit">Excluir postagem</button>
  </form>
<?php endif; ?>
