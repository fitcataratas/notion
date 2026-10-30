<?php
/** @var array $erros @var array $v @var array $setores @var array $projetos */
$val = fn(string $k) => e((string) ($v[$k] ?? ''));
$projSel = array_map('intval', (array) ($v['projetos'] ?? []));
?>
<div class="cabeca-pagina"><a class="voltar" href="/solicitacoes?minhas=1">← Meus pedidos</a></div>

<?php foreach ($erros as $er): ?><div class="aviso erro" role="alert"><?= e($er) ?></div><?php endforeach; ?>

<section class="cartao estreito">
  <p>Use este formulário para pedir <strong>artes, publicações, materiais digitais, impressos, apresentações</strong> e outras demandas do Marketing e do Design.</p>
  <p class="suave">Depois de enviar, o Marketing avalia o pedido, pode pedir mais informações, define o responsável e o prazo oficial e encaminha ao Design. Você acompanha tudo em "Meus pedidos".</p>
</section>

<form method="post" action="/solicitacoes" class="formulario cartao estreito ficha">
  <?= Csrf::campo() ?>
  <label class="campo-titulo">Nome do pedido
    <input type="text" name="title" value="<?= $val('title') ?>" required maxlength="255" autofocus placeholder="Ex.: Arte do Reels de abertura de vendas FIT 2027">
  </label>

  <div class="linha dois">
    <label>Tipo de material
      <select name="material_type" required>
        <option value="">Escolha…</option>
        <?php foreach (Config::TIPOS_MATERIAL as $m): ?><option<?= ($v['material_type'] ?? '') === $m ? ' selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>O seu setor
      <select name="setor_solicitante">
        <option value="">—</option>
        <?php foreach ($setores as $s): ?><option value="<?= (int) $s['id'] ?>"<?= (int) ($v['setor_solicitante'] ?? 0) === (int) $s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
  </div>

  <fieldset><legend>Para qual projeto?</legend>
    <div class="grade-marcas">
      <?php foreach ($projetos as $x): ?>
        <label class="marca" style="--c:<?= e(cor_valida($x['color'])) ?>"><input type="checkbox" name="projetos[]" value="<?= (int) $x['id'] ?>"<?= in_array((int) $x['id'], $projSel, true) ? ' checked' : '' ?>><span><?= e($x['name']) ?></span></label>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <label>O que você precisa? <small>(explique o pedido com o máximo de detalhes)</small>
    <textarea name="briefing" rows="5" maxlength="20000" required><?= $val('briefing') ?></textarea>
  </label>

  <div class="linha dois">
    <label>Preciso até
      <input type="date" name="requested_deadline" value="<?= $val('requested_deadline') ?>" min="<?= e(date('Y-m-d')) ?>" required>
    </label>
    <label>Data de publicação ou impressão <small>(se já souber)</small>
      <input type="date" name="publish_date" value="<?= $val('publish_date') ?>">
    </label>
  </div>

  <label>Objetivo da peça<textarea name="piece_goal" rows="2" maxlength="5000"><?= $val('piece_goal') ?></textarea></label>
  <label>Público alvo<textarea name="target_audience" rows="2" maxlength="5000"><?= $val('target_audience') ?></textarea></label>
  <label>Texto que deve aparecer na peça<textarea name="content" rows="4" maxlength="100000"><?= $val('content') ?></textarea></label>
  <label>Formato e tamanho <small>(se souber)</small><textarea name="format_specs" rows="2" maxlength="5000"><?= $val('format_specs') ?></textarea></label>
  <label>Referências <small>(links ou exemplos)</small><textarea name="references_text" rows="2" maxlength="10000"><?= $val('references_text') ?></textarea></label>
  <label>Pasta com os materiais <small>(cole o link do Drive, por exemplo)</small><input type="text" name="materials_folder" value="<?= $val('materials_folder') ?>" maxlength="2000"></label>
  <label>Outras observações<textarea name="requester_notes" rows="2" maxlength="10000"><?= $val('requester_notes') ?></textarea></label>

  <div class="acoes">
    <button class="btn primario" type="submit">Enviar pedido</button>
    <a class="btn texto" href="/solicitacoes?minhas=1">Cancelar</a>
  </div>
</form>
