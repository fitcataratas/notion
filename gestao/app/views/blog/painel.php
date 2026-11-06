<?php
/** @var array $colunas @var string $q @var bool $pode_avaliar @var array $pautas @var array $f @var array $resp */
$volta = $_SERVER['REQUEST_URI'] ?? '/blog';
$totalIdeias = array_sum(array_map('count', $colunas));
?>
<?php $lugar = 'topo'; include __DIR__ . '/_aviso.php'; ?>

<h2 class="titulo-secao">Banco de Ideias</h2>
<div class="cabeca-pagina cabeca-flex">
  <form method="get" action="/blog" class="filtros" role="search">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar ideia…" maxlength="100" aria-label="Buscar ideia">
    <button class="btn" type="submit">Buscar</button>
    <?php if ($q !== ''): ?><a class="btn texto" href="/blog">Limpar</a><?php endif; ?>
  </form>
  <details class="nova-ideia">
    <summary class="btn primario">+ Nova ideia</summary>
    <form method="post" action="/blog/ideias" class="formulario cartao">
      <?= Csrf::campo() ?>
      <label>Ideia <input type="text" name="title" required maxlength="255" placeholder="Qual o tema do artigo?"></label>
      <label>Detalhes <small>(opcional)</small><textarea name="notes" rows="3" maxlength="200000"></textarea></label>
      <div class="acoes"><button class="btn primario" type="submit">Cadastrar em “Sem Avaliação”</button></div>
    </form>
  </details>
</div>
<p class="suave"><?= $pode_avaliar ? 'Arraste a ideia de coluna (ou use “Mover para…”): ao ir para Aprovado a pauta nasce sozinha; Cancelado e Publicado atualizam a pauta.' : '' ?> (<?= (int) $totalIdeias ?> ideias)</p>

<?php $lugar = 'ideias'; include __DIR__ . '/_aviso.php'; ?>
<div class="quadro-pedidos quadro-blog" data-mover-campo="avaliacao">
  <?php foreach ($colunas as $av => $itens): ?>
    <section class="coluna" data-valor="<?= e($av) ?>" aria-label="<?= e($av) ?>">
      <header><?= chip($av, Blog::AVALIACOES[$av]) ?><span class="cont"><?= count($itens) ?></span></header>
      <div class="coluna-corpo">
      <?php foreach ($itens as $i): ?>
        <article<?= $pode_avaliar ? ' draggable="true" data-acao="/blog/ideias/' . (int) $i['id'] . '/avaliacao"' : '' ?> class="cartao-tarefa">
          <a class="cartao-titulo" href="/blog/ideias/<?= (int) $i['id'] ?>"><?= e($i['title']) ?></a>
          <?php if (!empty($resp[(int) $i['id']]) || $i['image_code']): ?><p class="suave linha-ideia"><?= !empty($resp[(int) $i['id']]) ? avatares($resp[(int) $i['id']], 4) : '' ?><?= $i['image_code'] ? ' <span title="Tem imagem">🖼</span>' : '' ?></p><?php endif; ?>
          <?php if ($i['pauta_id']): ?><p class="suave"><a href="/blog/pautas/<?= (int) $i['pauta_id'] ?>">Pauta: <?= e($i['pauta_status']) ?></a></p><?php endif; ?>
          <?php if ($pode_avaliar): ?>
            <form method="post" action="/blog/ideias/<?= (int) $i['id'] ?>/avaliacao" data-auto>
              <?= Csrf::campo() ?><input type="hidden" name="volta" value="<?= e($volta) ?>">
              <select class="mover" name="avaliacao" aria-label="Mover <?= e($i['title']) ?> para">
                <option value="">Mover para…</option>
                <?php foreach (array_keys(Blog::AVALIACOES) as $o): if ($o !== $av): ?><option><?= e($o) ?></option><?php endif; endforeach; ?>
              </select>
            </form>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
      <?php if (!$itens): ?><p class="coluna-vazia">Nenhuma ideia</p><?php endif; ?>
      </div>
    </section>
  <?php endforeach; ?>
</div>

<?php $lugar = 'pautas'; include __DIR__ . '/_aviso.php'; ?>
<h2 class="titulo-secao">Pautas</h2>
<div class="cabeca-pagina cabeca-flex">
  <form method="get" action="/blog" class="filtros">
    <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
    <select name="status" aria-label="Status da pauta"><option value="">Todos os status</option>
      <?php foreach (array_keys(Blog::STATUS_PAUTA) as $s): ?><option<?= $f['status'] === $s ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
    </select>
    <label class="marca-simples"><input type="checkbox" name="abertas" value="1"<?= $f['abertas'] ? ' checked' : '' ?>> Só em andamento</label>
    <button class="btn" type="submit">Filtrar</button>
    <?php if ($f['status'] !== '' || $f['abertas']): ?><a class="btn texto" href="/blog">Limpar</a><?php endif; ?>
  </form>
  <a class="btn" href="/blog/pautas/nova">+ Nova pauta</a>
</div>
<div class="tabela-rolagem">
<table class="tabela tabela-posts">
  <thead><tr><th>Título</th><th>Tema</th><th>Status da Pauta</th><th>Responsável da Pesquisa</th><th>Data de publicação</th><th>Prazo de Pesquisa</th><th>Data do Texto</th><th>Público alvo</th><th>Solicitante</th><th>Link do artigo</th></tr></thead>
  <tbody>
  <?php foreach ($pautas as $t): ?>
    <tr>
      <td class="col-titulo"><a href="/blog/pautas/<?= (int) $t['id'] ?>"><?= e($t['title']) ?></a></td>
      <td class="col-resumo"><?= e(mb_strimwidth((string) $t['theme'], 0, 70, '…')) ?></td>
      <td>
        <form method="post" action="/blog/pautas/<?= (int) $t['id'] ?>/status" data-auto>
          <?= Csrf::campo() ?><input type="hidden" name="volta" value="<?= e($volta) ?>">
          <select name="status" class="sel-chip" style="--c:<?= e(Blog::STATUS_PAUTA[$t['status']] ?? '#64748b') ?>" aria-label="Status de <?= e($t['title']) ?>">
            <?php foreach (array_keys(Blog::STATUS_PAUTA) as $s): ?><option<?= $t['status'] === $s ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
          </select>
        </form>
      </td>
      <td><?= $t['pesquisa_nome'] ? '<span class="pessoa">' . avatar_html(['name' => $t['pesquisa_nome'], 'photo' => $t['pesquisa_photo'], 'id' => $t['researcher_id']], 'peq') . e($t['pesquisa_nome']) . '</span>' : '' ?></td>
      <td class="nowrap"><?= $t['publish_date'] ? e(data_br($t['publish_date'])) : '<span class="suave">—</span>' ?></td>
      <td class="nowrap"><?= $t['prazo_pesquisa'] ? e(data_br($t['prazo_pesquisa'])) : '<span class="suave">—</span>' ?></td>
      <td class="nowrap"><?= $t['data_texto'] ? e(data_br($t['data_texto'])) : '<span class="suave">—</span>' ?></td>
      <td><?= e($t['audience'] ?? '') ?></td>
      <td><?= e($t['solicitante_nome'] ?? '') ?></td>
      <td><?php if ($t['article_url']): ?><a href="<?= e($t['article_url']) ?>" target="_blank" rel="noopener noreferrer">Abrir artigo</a><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$pautas): ?><tr><td colspan="10" class="vazio-linha">Nenhuma pauta ainda. Elas nascem quando uma ideia é aprovada.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php $lugar = 'fim'; include __DIR__ . '/_aviso.php'; ?>
