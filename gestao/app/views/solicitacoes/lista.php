<?php
/** @var array $colunas @var int $total @var array $projetos @var array $f @var string $hoje */
$u = Auth::usuario();
$externo = Perm::externo($u);
$volta = $_SERVER['REQUEST_URI'] ?? '/solicitacoes';
?>
<div class="cabeca-pagina cabeca-flex">
  <p class="suave">
    <?php if ($externo): ?>Aqui você acompanha os pedidos que fez ao Marketing e ao Design.
    <?php else: ?>Cada pedido é uma tarefa só: briefing, prazos, links e ajustes ficam todos nela. Mude a etapa conforme o pedido avança.<?php endif; ?>
  </p>
  <a class="btn primario" href="/solicitacoes/nova">+ Nova solicitação</a>
</div>

<?php if (!$externo): ?>
<form method="get" action="/solicitacoes" class="filtros" role="search">
  <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Buscar pedido…" maxlength="100" aria-label="Buscar">
  <select name="projeto" aria-label="Projeto"><option value="">Todos os projetos</option>
    <?php foreach ($projetos as $x): ?><option value="<?= (int) $x['id'] ?>"<?= $f['projeto'] === (int) $x['id'] ? ' selected' : '' ?>><?= e($x['name']) ?></option><?php endforeach; ?>
  </select>
  <label class="marca-simples"><input type="checkbox" name="minhas" value="1"<?= $f['minhas'] ? ' checked' : '' ?>> Só os que eu pedi</label>
  <button class="btn" type="submit">Filtrar</button>
  <?php if ($f['q'] || $f['projeto'] || $f['minhas']): ?><a class="btn texto" href="/solicitacoes">Limpar</a><?php endif; ?>
</form>
<?php endif; ?>

<p class="resumo"><?= (int) $total ?> pedido<?= $total === 1 ? '' : 's' ?>.</p>
<div class="quadro-pedidos" data-mover-campo="etapa">
  <?php foreach ($colunas as $etapa => $itens): ?>
    <section class="coluna" data-valor="<?= e($etapa) ?>" aria-label="<?= e($etapa) ?>">
      <header><span class="chip etapa-<?= e(slug($etapa)) ?>"><?= e($etapa) ?></span><span class="cont"><?= count($itens) ?></span></header>
      <div class="coluna-corpo">
        <?php foreach ($itens as $t): ?>
          <article<?= $externo ? '' : ' draggable="true" data-acao="/solicitacoes/' . (int) $t['id'] . '/etapa"' ?> class="cartao-tarefa<?= ($t['requested_deadline'] && $t['requested_deadline'] < $hoje && $etapa !== 'Concluída') ? ' atrasada' : '' ?>">
            <a class="cartao-titulo" href="/tarefas/<?= (int) $t['id'] ?>?<?= e(http_build_query(['volta' => $volta])) ?>"><?= e($t['title']) ?></a>
            <div class="chips">
              <?php if ($t['material_type']): ?><span class="chip" style="--c:#64748b"><?= e($t['material_type']) ?></span><?php endif; ?>
              <?php foreach ($t['projetos'] as $x): ?><?= chip($x['name'], $x['color']) ?><?php endforeach; ?>
            </div>
            <div class="meta">
              <?php if ($t['requested_deadline']): ?><span class="data" title="Prazo pedido">Pedido para <?= e(data_curta($t['requested_deadline'])) ?></span><?php endif; ?>
              <?php if ($t['publish_date']): ?><span class="data" title="Publicação ou impressão">Publ. <?= e(data_curta($t['publish_date'])) ?></span><?php endif; ?>
            </div>
            <div class="meta">
              <?php if ($t['solicitantes']): ?><span class="suave" title="Quem pediu">Pediu: <?= avatares($t['solicitantes']) ?></span><?php endif; ?>
              <?php if ($t['responsaveis']): ?><span class="suave" title="Responsável">Resp.: <?= avatares($t['responsaveis']) ?></span><?php endif; ?>
            </div>
            <?php if (!$externo): ?>
              <form method="post" action="/solicitacoes/<?= (int) $t['id'] ?>/etapa" data-auto>
                <?= Csrf::campo() ?><input type="hidden" name="volta" value="<?= e($volta) ?>">
                <select class="mover" name="etapa" aria-label="Mudar a etapa de “<?= e($t['title']) ?>”">
                  <option value="">Mover para…</option>
                  <?php foreach (Config::ETAPAS_MKT as $e2): if ($e2 !== $etapa): ?><option><?= e($e2) ?></option><?php endif; endforeach; ?>
                </select>
              </form>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
        <?php if (!$itens): ?><p class="coluna-vazia">Nenhum pedido</p><?php endif; ?>
      </div>
    </section>
  <?php endforeach; ?>
</div>
