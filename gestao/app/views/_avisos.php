<?php
/** Avisos e textos de uma página. Antes de incluir, defina $pagina e $lugar ('topo'|'fim'). Só o administrador vê os botões de editar. */
$eAdm = Perm::admin(Auth::usuario());
$lista = array_values(array_filter(Avisos::daPagina($pagina), fn($a) => $a['pos'] === $lugar));
$cores = Institucional::AVISO_CORES;
$campos = function (?array $a) use ($cores): void {
    $cor = $a['color'] ?? '#c98a00'; $ehPreset = isset($cores[$cor]); ?>
      <?= Csrf::campo() ?>
      <input type="hidden" name="volta" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
      <label>Texto <small>(links: https://… ou [texto](https://…))</small>
        <textarea name="texto" rows="3" maxlength="1500"><?= e($a['body'] ?? '') ?></textarea></label>
      <fieldset class="cores-aviso"><legend>Cor</legend>
        <?php foreach ($cores as $hex => $nome): ?>
          <label class="amostra" title="<?= e($nome) ?>"><input type="radio" name="cor" value="<?= e($hex) ?>"<?= $cor === $hex ? ' checked' : '' ?>><span style="--c:<?= e($hex) ?>"><?= e($nome) ?></span></label>
        <?php endforeach; ?>
        <label class="amostra"><input type="radio" name="cor" value="outra"<?= $ehPreset ? '' : ' checked' ?>><span>Outra: <input type="color" name="cor_outra" value="<?= e($cor) ?>" aria-label="Escolher outra cor"></span></label>
      </fieldset>
      <label>Aparência
        <select name="estilo"><?php foreach (Avisos::ESTILOS as $k => $n): ?><option value="<?= e($k) ?>"<?= ($a['style'] ?? 'faixa') === $k ? ' selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></label>
      <label>Onde aparece
        <select name="pos"><?php foreach (Avisos::POSICOES as $k => $n): ?><option value="<?= e($k) ?>"<?= ($a['pos'] ?? 'topo') === $k ? ' selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></label>
<?php };
foreach ($lista as $a): ?>
<div class="<?= $a['style'] === 'texto' ? 'texto-pagina' : 'aviso-faixa' ?>" style="--c:<?= e($a['color']) ?>">
  <p><?= texto_html($a['body']) ?></p>
  <?php if ($eAdm): ?>
  <details class="aviso-editar">
    <summary>✎ Editar</summary>
    <form method="post" action="/admin/avisos/<?= (int) $a['id'] ?>" class="formulario cartao">
      <?php $campos($a); ?>
      <div class="acoes"><button class="btn primario" type="submit">Salvar</button></div>
    </form>
    <form method="post" action="/admin/avisos/<?= (int) $a['id'] ?>/excluir" class="formulario" data-confirmar="Apagar este aviso?">
      <?= Csrf::campo() ?><input type="hidden" name="volta" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
      <button class="btn texto" type="submit">Apagar aviso</button>
    </form>
  </details>
  <?php endif; ?>
</div>
<?php endforeach;
if ($eAdm && ($lugar === 'topo' || $lista)): ?>
<details class="aviso-editar aviso-novo">
  <summary>+ Adicionar aviso ou texto <?= $lugar === 'fim' ? 'no fim da página' : '' ?></summary>
  <form method="post" action="/admin/avisos" class="formulario cartao">
    <input type="hidden" name="pagina" value="<?= e($pagina) ?>">
    <?php $campos(['pos' => $lugar]); ?>
    <div class="acoes"><button class="btn primario" type="submit">Adicionar</button></div>
  </form>
</details>
<?php endif; ?>
