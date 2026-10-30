<?php /** @var array $pg @var array $s */ $u = Auth::usuario(); ?>
<div class="cabeca-pagina"><a class="voltar" href="/setores/<?= (int) $pg['sector_id'] ?>">← <?= e($s['name'] ?? 'Setor') ?></a></div>
<article class="cartao pagina-texto">
  <header class="cartao-cab">
    <h2><?= e($pg['title']) ?></h2>
    <span class="acoes-linha">
      <a class="btn" href="/paginas/<?= (int) $pg['id'] ?>/editar">Editar</a>
      <?php if (Perm::admin($u)): ?>
        <form method="post" action="/paginas/<?= (int) $pg['id'] ?>/excluir" class="em-linha" data-confirmar="Excluir a página “<?= e($pg['title']) ?>” para sempre?">
          <?= Csrf::campo() ?><button class="btn perigo" type="submit">Excluir</button>
        </form>
      <?php endif; ?>
    </span>
  </header>
  <?php if (trim((string) $pg['body']) === ''): ?>
    <p class="suave">Esta página ainda está vazia. Clique em Editar para escrever.</p>
  <?php else: ?>
    <div class="texto-pagina"><?= texto_html($pg['body']) ?></div>
  <?php endif; ?>
  <p class="suave rodape-pagina">Criada por <?= e($pg['autor'] ?? '—') ?> · última edição por <?= e($pg['editor'] ?? '—') ?> em <?= e(datahora_br($pg['updated_at'])) ?></p>
</article>
