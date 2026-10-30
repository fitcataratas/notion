<?php /** @var array $blocos */ ?>
<p class="suave">As listas e as cores do Cronograma de Postagens. Mude o nome ou a cor e salve: vale para todas as postagens que usam o item.
  A cor da <strong>Situação do post</strong> também pinta os cartões do calendário. Só dá para excluir um item que nenhuma postagem use.</p>

<?php foreach ($blocos as $k => $b): ?>
<section class="cartao" id="<?= e($k) ?>">
  <h3><?= e($b['cfg'][0]) ?></h3>
  <div class="tabela-rolagem">
  <table class="tabela">
    <thead><tr><th colspan="4">Nome · cor · ordem na lista · quantas postagens usam</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($b['itens'] as $i): ?>
      <tr>
        <td colspan="4">
          <form method="post" action="/admin/postagens/item/<?= (int) $i['id'] ?>" class="linha-edicao">
            <?= Csrf::campo() ?>
            <input type="text" name="name" value="<?= e($i['name']) ?>" required maxlength="60" aria-label="Nome">
            <input type="color" name="color" value="<?= e($i['color']) ?>" aria-label="Cor">
            <input type="number" name="sort_order" value="<?= (int) $i['sort_order'] ?>" min="0" max="999" aria-label="Ordem" style="width:5rem">
            <span class="nowrap"><?= (int) $i['usos'] ?> uso<?= (int) $i['usos'] === 1 ? '' : 's' ?></span>
            <button class="btn" type="submit">Salvar</button>
          </form>
        </td>
        <td>
          <form method="post" action="/admin/postagens/item/<?= (int) $i['id'] ?>/excluir" data-confirmar="Excluir “<?= e($i['name']) ?>”?">
            <?= Csrf::campo() ?>
            <button class="btn perigo texto" type="submit"<?= (int) $i['usos'] > 0 ? ' disabled title="Em uso por postagens"' : '' ?>>Excluir</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <form method="post" action="/admin/postagens/<?= e($k) ?>" class="linha-edicao">
    <?= Csrf::campo() ?>
    <input type="text" name="name" required maxlength="60" placeholder="Novo item de <?= e($b['cfg'][0]) ?>" aria-label="Novo item">
    <input type="color" name="color" value="#64748b" aria-label="Cor">
    <button class="btn primario" type="submit">Adicionar</button>
  </form>
</section>
<?php endforeach; ?>
