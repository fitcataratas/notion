<?php
/** Faixa de aviso do Blog. Antes de incluir, defina $lugar ('topo'|'ideias'|'pautas'|'fim'|'pauta'). */
$av = Institucional::avisoBlogDados();
$eAdm = Perm::admin(Auth::usuario());
$aqui = $lugar === 'pauta' ? $av['na_pauta'] : ($av['pos'] === $lugar);
$vazioAdm = $eAdm && $av['texto'] === '' && $lugar === 'topo';   // sem aviso: o admin ainda vê "+ Adicionar aviso"
if (($av['texto'] !== '' && $aqui) || $vazioAdm):
  $cores = Institucional::AVISO_CORES; $ehPreset = isset($cores[$av['cor']]);
?>
<div class="aviso-faixa" style="--c:<?= e($av['cor']) ?>">
  <?php if ($av['texto'] !== ''): ?><p><?= e($av['texto']) ?></p><?php else: ?><p class="suave">Nenhum aviso no momento.</p><?php endif; ?>
  <?php if ($eAdm && $lugar !== 'pauta'): ?>
  <details class="aviso-editar">
    <summary><?= $av['texto'] === '' ? '+ Adicionar aviso' : '✎ Editar aviso' ?></summary>
    <form method="post" action="/admin/blog-aviso" class="formulario cartao">
      <?= Csrf::campo() ?>
      <label>Texto do aviso <small>(deixe em branco para esconder)</small>
        <textarea name="texto" rows="2" maxlength="300"><?= e($av['texto']) ?></textarea></label>
      <fieldset class="cores-aviso"><legend>Cor</legend>
        <?php foreach ($cores as $hex => $nome): ?>
          <label class="amostra" title="<?= e($nome) ?>"><input type="radio" name="cor" value="<?= e($hex) ?>"<?= $av['cor'] === $hex ? ' checked' : '' ?>><span style="--c:<?= e($hex) ?>"><?= e($nome) ?></span></label>
        <?php endforeach; ?>
        <label class="amostra"><input type="radio" name="cor" value="outra"<?= $ehPreset ? '' : ' checked' ?>><span>Outra: <input type="color" name="cor_outra" value="<?= e($av['cor']) ?>" aria-label="Escolher outra cor"></span></label>
      </fieldset>
      <label>Onde aparece
        <select name="pos"><?php foreach (Institucional::AVISO_POSICOES as $k => $n): ?><option value="<?= e($k) ?>"<?= $av['pos'] === $k ? ' selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></label>
      <label class="marca-simples"><input type="checkbox" name="na_pauta" value="1"<?= $av['na_pauta'] ? ' checked' : '' ?>> Mostrar também dentro de cada pauta</label>
      <div class="acoes"><button class="btn primario" type="submit">Salvar aviso</button></div>
    </form>
  </details>
  <?php endif; ?>
</div>
<?php endif; ?>
