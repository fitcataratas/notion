<?php /** @var array $i @var array $erros @var bool $pode_avaliar @var bool $pode_apagar @var array $pessoas */ ?>
<div class="cabeca-pagina"><a class="voltar" href="/blog">← Voltar ao Blog</a></div>
<?php foreach ($erros as $er): ?><div class="aviso erro" role="alert"><?= e($er) ?></div><?php endforeach; ?>

<form method="post" action="/blog/ideias/<?= (int) $i['id'] ?>" class="formulario cartao ficha" enctype="multipart/form-data">
  <?= Csrf::campo() ?>
  <label class="campo-titulo">Ideia <input type="text" name="title" value="<?= e($i['title']) ?>" required maxlength="255"></label>
  <p class="suave">Avaliação: <?= chip($i['evaluation'], Blog::AVALIACOES[$i['evaluation']] ?? '#64748b') ?>
    <?php if ($i['autor']): ?> · cadastrada por <?= e($i['autor']) ?><?php endif; ?> em <?= e(datahora_br($i['created_at'])) ?></p>
  <?php if ($i['pauta_id']): ?><p>Pauta criada: <a href="/blog/pautas/<?= (int) $i['pauta_id'] ?>"><?= e($i['pauta_status']) ?> — abrir pauta</a></p><?php endif; ?>
  <fieldset><legend>Responsáveis <small>(quem cuida desta ideia; recebem um aviso no sininho)</small></legend>
    <div class="grade-marcas">
      <?php foreach ($pessoas as $pp): ?>
        <label class="marca"><input type="checkbox" name="responsaveis[]" value="<?= (int) $pp['id'] ?>"<?= in_array((int) $pp['id'], $i['_resp'] ?? [], true) ? ' checked' : '' ?>><span><?= e($pp['name']) ?></span></label>
      <?php endforeach; ?>
    </div>
  </fieldset>
  <fieldset class="imagem-ideia"><legend>Imagem do artigo <small>(a que será publicada junto com o texto)</small></legend>
    <?php if (!empty($i['image_code'])): ?>
      <a href="/blog/ideias/<?= (int) $i['id'] ?>/imagem" target="_blank" rel="noopener"><img class="previa-imagem" src="/blog/ideias/<?= (int) $i['id'] ?>/imagem" alt="Imagem da ideia <?= e($i['title']) ?>"></a>
      <p><a class="btn" href="/blog/ideias/<?= (int) $i['id'] ?>/imagem?baixar=1">Baixar imagem</a>
        <label class="marca-simples"><input type="checkbox" name="remover_imagem" value="1"> Remover esta imagem</label></p>
    <?php endif; ?>
    <input type="file" name="imagem" accept="image/jpeg,image/png,image/webp" aria-label="Escolher imagem">
    <small>JPG, PNG ou WEBP, até 8 MB. <?= !empty($i['image_code']) ? 'Escolher outra troca a atual. ' : '' ?>Para colocar PDF ou planilha, use o link do Drive no texto.</small>
  </fieldset>
  <div class="campo-rico"><span class="rotulo" id="rot-notas">Texto e detalhes</span>
    <textarea name="notes" rows="16" maxlength="400000" data-rico aria-labelledby="rot-notas"><?= e(RichText::paraEditor($i['notes'] ?? '', !empty($i['notes_rich']))) ?></textarea></div>
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
  <?php if ($pode_apagar && !$i['pauta_id']): ?>
  <form method="post" action="/blog/ideias/<?= (int) $i['id'] ?>/excluir" class="excluir" data-confirmar="Apagar esta ideia?"><?= Csrf::campo() ?><button class="btn perigo texto" type="submit">Apagar ideia</button></form>
  <?php elseif ($i['pauta_id']): ?><p class="suave">Esta ideia já gerou uma pauta e não pode ser apagada: mova para “Cancelado”.</p><?php endif; ?>
</section>
<?php endif; ?>
