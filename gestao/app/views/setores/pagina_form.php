<?php /** @var array $s @var array $pg @var int|null $id @var array $erros */ ?>
<div class="cabeca-pagina"><a class="voltar" href="<?= $id ? '/paginas/' . (int) $id : '/setores/' . (int) $s['id'] ?>">← Voltar</a></div>
<?php foreach ($erros as $er): ?><div class="aviso erro" role="alert"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" action="<?= $id ? '/paginas/' . (int) $id : '/setores/' . (int) $s['id'] . '/paginas' ?>" class="formulario cartao ficha">
  <?= Csrf::campo() ?>
  <p class="suave">Página do setor <strong><?= e($s['name']) ?></strong>.</p>
  <label class="campo-titulo">Título<input type="text" name="title" value="<?= e($pg['title'] ?? '') ?>" required maxlength="160" autofocus></label>
  <label>Texto <small>(linhas em branco separam parágrafos; links começando com http:// ou https:// ficam clicáveis)</small>
    <textarea name="body" rows="22" maxlength="200000"><?= e($pg['body'] ?? '') ?></textarea>
  </label>
  <div class="acoes">
    <button class="btn primario" type="submit">Salvar</button>
    <a class="btn texto" href="<?= $id ? '/paginas/' . (int) $id : '/setores/' . (int) $s['id'] ?>">Cancelar</a>
  </div>
</form>
