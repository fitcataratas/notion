<?php
/** @var array $v @var ?int $id @var array $erros @var array $perfis @var array $pessoas @var string $volta */
$val = fn(string $k) => $v[$k] ?? '';
$acao = $id ? "/editorial/$id" : '/editorial';
?>
<div class="cabeca-pagina"><a class="voltar" href="<?= e($volta) ?>">← Voltar ao dashboard</a></div>
<?php foreach ($erros as $er): ?><div class="aviso erro" role="alert"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" action="<?= e($acao) ?>" class="formulario cartao ficha">
  <?= Csrf::campo() ?><input type="hidden" name="volta" value="<?= e($volta) ?>">
  <label class="campo-titulo">Título / ideia<input type="text" name="title" value="<?= e($val('title')) ?>" required maxlength="255" autofocus></label>
  <div class="linha tres">
    <label>Perfil
      <select name="profile_id" required><option value="">—</option>
        <?php foreach ($perfis as $p): ?><option value="<?= (int) $p['id'] ?>"<?= (int) $val('profile_id') === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Rede / canal
      <select name="network"><?php foreach (Editorial::REDES as $r): ?><option<?= $val('network') === $r ? ' selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select>
    </label>
    <label>Formato
      <select name="format"><?php foreach (Editorial::FORMATOS as $r): ?><option<?= $val('format') === $r ? ' selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select>
    </label>
  </div>
  <div class="linha tres">
    <label>Data de publicação<input type="date" name="pub_date" value="<?= e($val('pub_date')) ?>" required></label>
    <label>Status
      <select name="status"><?php foreach (array_keys(Editorial::STATUS) as $r): ?><option<?= $val('status') === $r ? ' selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select>
    </label>
    <label>Responsável <small>(recebe uma notificação)</small>
      <select name="owner_id"><option value="">—</option>
        <?php foreach ($pessoas as $p): ?><option value="<?= (int) $p['id'] ?>"<?= (int) $val('owner_id') === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
  </div>
  <label>Conteúdo <small>(legenda, roteiro ou texto)</small><textarea name="content" rows="10" maxlength="100000" placeholder="Escreva aqui o conteúdo…"><?= e($val('content')) ?></textarea></label>
  <div class="acoes">
    <button class="btn primario" type="submit"><?= $id ? 'Salvar alterações' : 'Criar conteúdo' ?></button>
    <a class="btn texto" href="<?= e($volta) ?>">Cancelar</a>
  </div>
</form>
<?php if ($id && Perm::admin(Auth::usuario())): ?>
  <form method="post" action="/editorial/<?= (int) $id ?>/excluir" class="excluir" data-confirmar="Excluir este conteúdo para sempre?">
    <?= Csrf::campo() ?><input type="hidden" name="volta" value="<?= e($volta) ?>"><button class="btn perigo texto" type="submit">Excluir conteúdo</button>
  </form>
<?php endif; ?>
