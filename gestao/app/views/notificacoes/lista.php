<?php /** @var array $itens */ ?>
<div class="barra-visoes">
  <p class="suave">Aqui aparecem os avisos sobre o que é seu: tarefas, pedidos, postagens, pautas e reuniões.</p>
  <?php if ($itens): ?>
  <form method="post" action="/notificacoes/lidas"><?= Csrf::campo() ?><button class="btn" type="submit">Marcar todas como lidas</button></form>
  <?php endif; ?>
</div>
<?php if (!$itens): ?>
  <p class="vazio">Você não tem notificações por enquanto.</p>
<?php else: ?>
<ul class="lista-notif cartao">
  <?php foreach ($itens as $n): ?>
    <li class="<?= $n['read_at'] ? 'lida' : 'nova' ?>">
      <a href="/notificacoes/<?= (int) $n['id'] ?>/abrir">
        <span class="notif-texto"><?= e($n['body']) ?></span>
        <small class="suave"><?= e(datahora_br($n['created_at'])) ?></small>
      </a>
    </li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>
