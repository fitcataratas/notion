<?php /** @var string $rota @var array $cfg @var array $itens */ ?>
<p class="suave">
  <?= $rota === 'setores'
      ? 'Os setores aparecem no menu, nos filtros e nas tarefas. Para trocar o nome ou a cor, edite e salve.'
      : 'Os projetos classificam as tarefas (por exemplo, FITCATARATAS). Para trocar o nome ou a cor, edite e salve.' ?>
  Só dá para excluir um item que nenhuma tarefa use.
</p>

<div class="tabela-rolagem">
<table class="tabela">
  <thead><tr><th>Nome</th><th>Cor</th><th>Tarefas</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($itens as $i): ?>
    <tr>
      <td colspan="3">
        <form method="post" action="/admin/<?= e($rota) ?>/<?= (int) $i['id'] ?>" class="linha-edicao">
          <?= Csrf::campo() ?>
          <input type="text" name="name" value="<?= e($i['name']) ?>" required maxlength="80" aria-label="Nome">
          <input type="color" name="color" value="<?= e($i['color']) ?>" aria-label="Cor">
          <span class="nowrap"><?= (int) $i['usos'] ?> tarefa<?= (int) $i['usos'] === 1 ? '' : 's' ?></span>
          <button class="btn" type="submit">Salvar</button>
          <?php if ($rota === 'setores'): ?><a class="btn texto" href="/admin/setores/<?= (int) $i['id'] ?>/detalhes">Responsáveis e descrição</a><?php endif; ?>
        </form>
      </td>
      <td>
        <form method="post" action="/admin/<?= e($rota) ?>/<?= (int) $i['id'] ?>/excluir" data-confirmar="Excluir “<?= e($i['name']) ?>”?">
          <?= Csrf::campo() ?>
          <button class="btn perigo texto" type="submit"<?= (int) $i['usos'] > 0 ? ' disabled title="Em uso por tarefas"' : '' ?>>Excluir</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<section class="cartao estreito">
  <h3>Novo <?= e($cfg['um']) ?></h3>
  <form method="post" action="/admin/<?= e($rota) ?>" class="linha-edicao">
    <?= Csrf::campo() ?>
    <input type="text" name="name" required maxlength="80" placeholder="Nome" aria-label="Nome">
    <input type="color" name="color" value="#64748b" aria-label="Cor">
    <button class="btn primario" type="submit">Adicionar</button>
  </form>
</section>
