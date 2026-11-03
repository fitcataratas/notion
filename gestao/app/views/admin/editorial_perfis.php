<?php /** @var array $perfis */ ?>
<p><a class="btn texto" href="/editorial">← Voltar ao Dashboard Editorial</a></p>
<p class="suave">Perfis são as “linhas” do quadro (ex.: FITCataratas, Hackatour). Apagar um perfil apaga também os conteúdos dele.</p>
<section class="cartao estreito">
  <?php foreach ($perfis as $p): ?>
    <div class="linha-perfil">
      <form method="post" action="/admin/editorial/<?= (int) $p['id'] ?>" class="linha-perfil">
        <?= Csrf::campo() ?>
        <input type="color" name="color" value="<?= e(cor_valida($p['color'])) ?>" aria-label="Cor de <?= e($p['name']) ?>">
        <input type="text" name="name" value="<?= e($p['name']) ?>" maxlength="80" required aria-label="Nome do perfil">
        <small class="suave"><?= (int) $p['total'] ?> conteúdo<?= $p['total'] == 1 ? '' : 's' ?></small>
        <button class="btn" type="submit">Salvar</button>
      </form>
      <form method="post" action="/admin/editorial/<?= (int) $p['id'] ?>/excluir" class="em-linha" data-confirmar="Remover este perfil e os <?= (int) $p['total'] ?> conteúdos dele?">
        <?= Csrf::campo() ?><button class="btn perigo texto" type="submit">Remover</button>
      </form>
    </div>
  <?php endforeach; ?>
</section>
<section class="cartao estreito">
  <h3>Novo perfil</h3>
  <form method="post" action="/admin/editorial" class="linha-perfil">
    <?= Csrf::campo() ?>
    <input type="color" name="color" value="#2f5bea" aria-label="Cor">
    <input type="text" name="name" maxlength="80" required placeholder="Ex.: Instagram do Festival" aria-label="Nome do novo perfil">
    <button class="btn primario" type="submit">Adicionar</button>
  </form>
</section>
