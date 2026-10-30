<?php /** @var array $linhas @var array $pessoas @var string $aviso */
$form = function (?array $l) use ($pessoas): string {
    $id = $l['id'] ?? null;
    ob_start(); ?>
    <form method="post" action="/admin/quem-faz<?= $id ? '/' . (int) $id : '' ?>" class="formulario">
      <?= Csrf::campo() ?>
      <div class="linha tres">
        <label>Nome<input type="text" name="person_name" value="<?= e($l['person_name'] ?? '') ?>" required maxlength="120"></label>
        <label>Setor / função<input type="text" name="role_label" value="<?= e($l['role_label'] ?? '') ?>" required maxlength="160"></label>
        <label>Login ligado <small>(para a foto)</small>
          <select name="user_id"><option value="">—</option>
            <?php foreach ($pessoas as $p): ?><option value="<?= (int) $p['id'] ?>"<?= (int) ($l['user_id'] ?? 0) === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
          </select>
        </label>
      </div>
      <div class="linha dois">
        <label>Responsável por <small>(um item por linha)</small><textarea name="duties" rows="5" maxlength="5000"><?= e($l['duties'] ?? '') ?></textarea></label>
        <label>Procure para <small>(um item por linha)</small><textarea name="contact_for" rows="5" maxlength="5000"><?= e($l['contact_for'] ?? '') ?></textarea></label>
      </div>
      <label>Ordem<input type="number" name="sort_order" min="0" max="999" value="<?= (int) ($l['sort_order'] ?? 0) ?>"></label>
      <div class="acoes"><button class="btn primario" type="submit"><?= $id ? 'Salvar' : 'Incluir pessoa' ?></button></div>
    </form>
    <?php return ob_get_clean();
};
?>
<p><a class="btn texto" href="/quem-faz-o-que">← Ver a página como a equipe vê</a></p>
<section class="cartao estreito">
  <h3>Aviso no topo</h3>
  <form method="post" action="/admin/quem-faz/aviso" class="formulario"><?= Csrf::campo() ?>
    <label>Texto<textarea name="quem_faz_aviso" rows="2" maxlength="1000"><?= e($aviso) ?></textarea></label>
    <div class="acoes"><button class="btn" type="submit">Salvar aviso</button></div>
  </form>
</section>
<?php foreach ($linhas as $l): ?>
  <details class="cartao" id="l<?= (int) $l['id'] ?>">
    <summary><strong><?= e($l['person_name']) ?></strong> <span class="suave">· <?= e($l['role_label']) ?></span></summary>
    <?= $form($l) ?>
    <form method="post" action="/admin/quem-faz/<?= (int) $l['id'] ?>/excluir" class="excluir" data-confirmar="Remover esta pessoa da página?"><?= Csrf::campo() ?><button class="btn perigo texto" type="submit">Remover</button></form>
  </details>
<?php endforeach; ?>
<section class="cartao"><h3>Incluir pessoa</h3><?= $form(null) ?></section>
