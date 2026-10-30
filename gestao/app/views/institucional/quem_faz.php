<?php /** @var array $linhas @var string $aviso */ ?>
<?php if (trim($aviso) !== ''): ?><section class="datas-importantes"><p><?= texto_html($aviso) ?></p></section><?php endif; ?>
<?php if (Perm::admin(Auth::usuario())): ?><p><a class="btn" href="/admin/quem-faz">Editar esta página</a></p><?php endif; ?>
<div class="grade-pessoas">
<?php foreach ($linhas as $l): ?>
  <article class="cartao ficha-pessoa">
    <header>
      <?= avatar_html(['name' => $l['person_name'], 'photo' => $l['photo'] ?? null]) ?>
      <div><h3><?= e($l['person_name']) ?></h3><small class="suave"><?= e($l['role_label']) ?></small></div>
    </header>
    <?php if ($l['lista_duties']): ?><h4>Responsável por</h4><ul><?php foreach ($l['lista_duties'] as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if ($l['lista_contact']): ?><h4>Procure para</h4><ul><?php foreach ($l['lista_contact'] as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul><?php endif; ?>
  </article>
<?php endforeach; ?>
<?php if (!$linhas): ?><p class="vazio">Nada cadastrado ainda.</p><?php endif; ?>
</div>
