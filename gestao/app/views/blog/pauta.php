<?php
/** @var array $v @var ?int $id @var array $erros @var array $pessoas */
$val = fn(string $k) => $v[$k] ?? '';
$pess = function (string $campo) use ($pessoas, $val): string {
    $h = '<option value="">—</option>';
    foreach ($pessoas as $p) {
        $h .= '<option value="' . (int) $p['id'] . '"' . ((int) $val($campo) === (int) $p['id'] ? ' selected' : '') . '>' . e($p['name']) . '</option>';
    }
    return $h;
};
[$prazoPesq, $dataTexto] = Blog::prazos($val('publish_date') ?: null);
?>
<div class="cabeca-pagina"><a class="voltar" href="/blog">← Voltar ao Blog</a></div>
<?php foreach ($erros as $er): ?><div class="aviso erro" role="alert"><?= e($er) ?></div><?php endforeach; ?>
<div class="aviso-segunda">PUBLICAÇÃO TODA SEGUNDA! Só preencher a “Data de Publicação”!</div>
<?php if ($val('publish_date') && data_valida((string) $val('publish_date')) && !Blog::ehSegunda((string) $val('publish_date'))): ?>
  <div class="aviso">Atenção: a data de publicação escolhida não é uma segunda-feira.</div>
<?php endif; ?>

<form method="post" action="<?= $id ? '/blog/pautas/' . (int) $id : '/blog/pautas' ?>" class="formulario cartao ficha">
  <?= Csrf::campo() ?>
  <label class="campo-titulo">Título <input type="text" name="title" value="<?= e($val('title')) ?>" required maxlength="255" autofocus></label>
  <?php if ($val('idea_id')): ?><p class="suave">Ideia de origem: <a href="/blog/ideias/<?= (int) $val('idea_id') ?>"><?= e($val('ideia_titulo')) ?></a></p><?php endif; ?>

  <div class="linha tres">
    <label>Status da Pauta
      <select name="status"><?php foreach (array_keys(Blog::STATUS_PAUTA) as $s): ?><option<?= ($val('status') ?: 'Ideia recebida') === $s ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
    </label>
    <label>Data de publicação <small>(só esta é preenchida à mão)</small><input type="date" name="publish_date" value="<?= e($val('publish_date')) ?>"></label>
    <label>Público alvo
      <input type="text" name="audience" value="<?= e($val('audience')) ?>" maxlength="160" list="publicos">
      <datalist id="publicos"><?php foreach (Blog::PUBLICOS as $p): ?><option value="<?= e($p) ?>"><?php endforeach; ?></datalist>
    </label>
  </div>
  <div class="linha dois">
    <label>Prazo de Pesquisa <small>(publicação − <?= Blog::DIAS_PESQUISA ?> dias, calculado)</small><input type="text" value="<?= $prazoPesq ? e(data_br($prazoPesq)) : '—' ?>" disabled></label>
    <label>Data do Texto <small>(publicação − <?= Blog::DIAS_TEXTO ?> dias, calculado)</small><input type="text" value="<?= $dataTexto ? e(data_br($dataTexto)) : '—' ?>" disabled></label>
  </div>
  <div class="linha dois">
    <label>Responsável da Pesquisa<select name="researcher_id"><?= $pess('researcher_id') ?></select></label>
    <label>Solicitante<select name="requester_id"><?= $pess('requester_id') ?></select></label>
  </div>
  <label>Tema <small>(assunto central pedido)</small><textarea name="theme" rows="3" maxlength="5000"><?= e($val('theme')) ?></textarea></label>
  <label>Link do artigo <small>(depois de publicado)</small><input type="text" name="article_url" value="<?= e($val('article_url')) ?>" maxlength="500" placeholder="https://"></label>
  <label>Anotações <textarea name="notes" rows="8" maxlength="200000"><?= e($val('notes')) ?></textarea></label>
  <div class="acoes"><button class="btn primario" type="submit"><?= $id ? 'Salvar alterações' : 'Criar pauta' ?></button> <a class="btn texto" href="/blog">Cancelar</a></div>
</form>

<?php if ($id && Perm::admin(Auth::usuario())): ?>
  <form method="post" action="/blog/pautas/<?= (int) $id ?>/excluir" class="excluir" data-confirmar="Excluir esta pauta para sempre?"><?= Csrf::campo() ?><button class="btn perigo texto" type="submit">Excluir pauta</button></form>
<?php endif; ?>
