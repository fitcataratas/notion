<?php /** @var array $colunas @var int $total @var string $intro */ ?>
<?php if (trim($intro) !== ''): ?><section class="cartao"><p><?= texto_html($intro) ?></p></section><?php endif; ?>
<p class="resumo"><?= (int) $total ?> tarefa<?= $total == 1 ? '' : 's' ?> com você como responsável. Para pedir algo à equipe, use <a href="/solicitacoes">Meus pedidos</a>.</p>
<div class="quadro">
<?php foreach ($colunas as $status => $itens): ?>
  <section class="coluna" aria-label="<?= e($status) ?>">
    <header><?= chip_status($status) ?><span class="cont"><?= count($itens) ?></span></header>
    <div class="coluna-corpo">
    <?php foreach ($itens as $t): ?>
      <article class="cartao-tarefa">
        <a href="/tarefas/<?= (int) $t['id'] ?>"><strong><?= e($t['title']) ?></strong></a>
        <?php if (!empty($t['start_date'])): ?><small class="suave">Data: <?= e(data_tarefa($t)) ?></small><?php endif; ?>
        <form method="post" action="/tarefas/<?= (int) $t['id'] ?>/status" data-auto>
          <?= Csrf::campo() ?><input type="hidden" name="volta" value="/espaco">
          <label class="sr" for="s<?= (int) $t['id'] ?>">Mudar situação</label>
          <select id="s<?= (int) $t['id'] ?>" name="status" class="sel-chip">
            <?php foreach (Config::STATUS as $s): ?><option<?= $s === $status ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
          </select>
        </form>
      </article>
    <?php endforeach; ?>
    <?php if (!$itens): ?><p class="coluna-vazia">Nenhuma tarefa</p><?php endif; ?>
    </div>
  </section>
<?php endforeach; ?>
</div>
