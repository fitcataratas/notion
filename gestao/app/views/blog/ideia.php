<?php /** @var array $i @var array $erros @var bool $pode_avaliar */ ?>
<div class="cabeca-pagina"><a class="voltar" href="/blog">← Voltar ao Blog</a></div>
<?php foreach ($erros as $er): ?><div class="aviso erro" role="alert"><?= e($er) ?></div><?php endforeach; ?>

<form method="post" action="/blog/ideias/<?= (int) $i['id'] ?>" class="formulario cartao ficha">
  <?= Csrf::campo() ?>
  <label class="campo-titulo">Ideia <input type="text" name="title" value="<?= e($i['title']) ?>" required maxlength="255"></label>
  <p class="suave">Avaliação: <?= chip($i['evaluation'], Blog::AVALIACOES[$i['evaluation']] ?? '#64748b') ?>
    <?php if ($i['autor']): ?> · cadastrada por <?= e($i['autor']) ?><?php endif; ?> em <?= e(datahora_br($i['created_at'])) ?></p>
  <?php if ($i['pauta_id']): ?><p>Pauta criada: <a href="/blog/pautas/<?= (int) $i['pauta_id'] ?>"><?= e($i['pauta_status']) ?> — abrir pauta</a></p><?php endif; ?>
  <label>Texto e detalhes <textarea name="notes" rows="16" maxlength="200000"><?= e($i['notes'] ?? '') ?></textarea></label>
  <div class="acoes"><button class="btn primario" type="submit">Salvar</button> <a class="btn texto" href="/blog">Cancelar</a></div>
</form>

<?php if ($pode_avaliar): ?>
<section class="cartao estreito">
  <h3>Avaliação</h3>
  <form method="post" action="/blog/ideias/<?= (int) $i['id'] ?>/avaliacao" class="linha-edicao">
    <?= Csrf::campo() ?><input type="hidden" name="volta" value="/blog/ideias/<?= (int) $i['id'] ?>">
    <select name="avaliacao" aria-label="Avaliação">
      <?php foreach (array_keys(Blog::AVALIACOES) as $o): ?><option<?= $i['evaluation'] === $o ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Mudar avaliação</button>
  </form>
  <p class="suave">Aprovado cria a pauta (uma só por ideia). Cancelado e Publicado atualizam a pauta. Reprovado = nunca foi aprovada; Cancelado = foi aprovada e depois interrompida.</p>
  <?php if (!$i['pauta_id']): ?>
  <form method="post" action="/blog/ideias/<?= (int) $i['id'] ?>/excluir" class="excluir" data-confirmar="Apagar esta ideia?"><?= Csrf::campo() ?><button class="btn perigo texto" type="submit">Apagar ideia</button></form>
  <?php else: ?><p class="suave">Esta ideia já gerou uma pauta e não pode ser apagada: mova para “Cancelado”.</p><?php endif; ?>
</section>
<?php endif; ?>
