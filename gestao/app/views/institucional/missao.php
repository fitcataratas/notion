<?php /** @var string $missao @var string $visao @var string $valores */ ?>
<div class="duas-colunas iguais">
  <section class="cartao"><h3>Missão</h3><?= trim($missao) !== '' ? '<p>' . texto_html($missao) . '</p>' : '<p class="suave">Ainda não preenchida.</p>' ?></section>
  <section class="cartao"><h3>Visão</h3><?= trim($visao) !== '' ? '<p>' . texto_html($visao) . '</p>' : '<p class="suave">Ainda não preenchida.</p>' ?></section>
</div>
<section class="cartao"><h3>Valores</h3><?= trim($valores) !== '' ? '<p>' . texto_html($valores) . '</p>' : '<p class="suave">Ainda não preenchidos.</p>' ?></section>
<?php if (Perm::admin(Auth::usuario())): ?><p><a class="btn" href="/admin/inicio">Editar estes textos</a></p><?php endif; ?>
