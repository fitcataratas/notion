<?php
/** @var array $s @var array $contagem @var array $abertas @var int $abertas_total @var array $paginas @var array $links @var string $hoje @var bool $recebe_pedidos */
$u = Auth::usuario();
$id = (int) $s['id'];
$volta = "/setores/$id";
$total = array_sum($contagem);
?>
<section class="cartao cabeca-setor" style="--c:<?= e(cor_valida($s['color'])) ?>">
  <div class="cabeca-setor-topo">
    <div>
      <?php if ($s['pai']): ?><p class="suave migalha"><a href="/setores/<?= (int) $s['pai']['id'] ?>"><?= e($s['pai']['name']) ?></a> /</p><?php endif; ?>
      <h2><span class="pt" style="--c:<?= e(cor_valida($s['color'])) ?>"></span> <?= e($s['name']) ?></h2>
    </div>
    <?php if (Perm::admin($u)): ?><a class="btn" href="/admin/setores/<?= $id ?>/detalhes">Editar descrição e responsáveis</a><?php endif; ?>
  </div>
  <?php if (!empty($s['description'])): ?><p class="descricao-setor"><?= texto_html($s['description']) ?></p><?php endif; ?>
  <?php if ($s['responsaveis']): ?>
    <ul class="responsaveis">
      <?php foreach ($s['responsaveis'] as $r): ?>
        <li>
          <?= avatar_html($r) ?>
          <span><strong><?= e($r['name']) ?></strong><small><?= e($r['role_label']) ?><?= $r['email'] ? ' · <a href="mailto:' . e($r['email']) . '">' . e($r['email']) . '</a>' : '' ?></small></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php elseif (Perm::admin($u)): ?>
    <p class="suave">Ainda não há responsáveis cadastrados. <a href="/admin/setores/<?= $id ?>/detalhes">Cadastrar</a></p>
  <?php endif; ?>
  <nav class="atalhos-setor" aria-label="Ver as tarefas do setor">
    <a class="btn primario" href="/tarefas/nova?setor=<?= $id ?>&amp;volta=<?= urlencode($volta) ?>">+ Nova tarefa</a>
    <a class="btn" href="/tarefas?view=quadro&amp;setor=<?= $id ?>">Quadro</a>
    <a class="btn" href="/tarefas?view=calendario&amp;setor=<?= $id ?>">Calendário</a>
    <a class="btn" href="/tarefas?view=tabela&amp;setor=<?= $id ?>">Tabela</a>
    <?php if ($recebe_pedidos): ?><a class="btn" href="/solicitacoes">Solicitações de Marketing e Design</a><?php endif; ?>
  </nav>
</section>

<?php if ($s['filhos']): ?>
  <section class="cartao">
    <h3>Dentro deste setor</h3>
    <div class="chips">
      <?php foreach ($s['filhos'] as $f): ?><a class="chip chip-link" style="--c:<?= e(cor_valida($f['color'])) ?>" href="/setores/<?= (int) $f['id'] ?>"><?= e($f['name']) ?></a><?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<div class="duas-colunas iguais">
  <section class="cartao">
    <header class="cartao-cab"><h3>Tarefas do setor</h3><a href="/tarefas?view=quadro&amp;setor=<?= $id ?>">ver o quadro</a></header>
    <div class="resumo-status">
      <?php foreach ($contagem as $st => $n): ?>
        <a class="resumo-item" href="/tarefas?view=tabela&amp;setor=<?= $id ?>&amp;status=<?= urlencode($st) ?>"><?= chip_status($st) ?><strong><?= (int) $n ?></strong></a>
      <?php endforeach; ?>
    </div>
    <?php if (!$abertas): ?>
      <p class="suave">Nenhuma tarefa em aberto neste setor.</p>
    <?php else: ?>
      <ul class="lista-tarefas">
        <?php foreach ($abertas as $t): ?>
          <li>
            <a href="/tarefas/<?= (int) $t['id'] ?>?<?= e(http_build_query(['volta' => $volta])) ?>"><?= e($t['title']) ?></a>
            <span class="meta">
              <form method="post" action="/tarefas/<?= (int) $t['id'] ?>/status" data-auto class="em-linha">
                <?= Csrf::campo() ?><input type="hidden" name="volta" value="<?= e($volta) ?>">
                <select name="status" class="sel-chip st-<?= e(slug($t['status'])) ?>" aria-label="Status de <?= e($t['title']) ?>"><?php foreach (Config::STATUS as $stt): ?><option<?= $stt === $t['status'] ? ' selected' : '' ?>><?= e($stt) ?></option><?php endforeach; ?></select>
              </form><?= chip_prioridade($t['priority']) ?>
              <?php if ($t['start_date']): ?><span class="data<?= tarefa_atrasada($t, $hoje) ? ' alerta' : '' ?>"><?= e(data_tarefa($t)) ?></span><?php endif; ?>
              <?= avatares($t['responsaveis']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php if ($abertas_total > count($abertas)): ?><p class="suave">E mais <?= $abertas_total - count($abertas) ?> em aberto…</p><?php endif; ?>
    <?php endif; ?>
  </section>

  <div>
    <section class="cartao" id="paginas">
      <header class="cartao-cab"><h3>Páginas do setor</h3><a href="/setores/<?= $id ?>/paginas/nova">+ Nova página</a></header>
      <?php if (!$paginas): ?>
        <p class="suave">Ainda não há páginas. Use para guardar orientações, processos, textos e o que a equipe do setor precisa consultar.</p>
      <?php else: ?>
        <ul class="lista-simples">
          <?php foreach ($paginas as $pg): ?>
            <li><a href="/paginas/<?= (int) $pg['id'] ?>"><?= e($pg['title']) ?></a> <small class="suave">atualizada em <?= e(data_br(substr((string) $pg['updated_at'], 0, 10))) ?></small></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="cartao" id="documentos">
      <h3>Documentos e links</h3>
      <?php if (!$links): ?><p class="suave">Nenhum link ainda. Cole aqui o endereço de planilhas, documentos e orçamentos do setor.</p><?php endif; ?>
      <?php foreach ($links as $grupo => $itens): ?>
        <h4 class="grupo-links"><?= e($grupo) ?></h4>
        <ul class="lista-simples">
          <?php foreach ($itens as $l): ?>
            <li>
              <a href="<?= e($l['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($l['title']) ?></a>
              <form method="post" action="/links/<?= (int) $l['id'] ?>/excluir" class="em-linha" data-confirmar="Remover o link “<?= e($l['title']) ?>”?">
                <?= Csrf::campo() ?><button class="link" type="submit" aria-label="Remover link <?= e($l['title']) ?>">remover</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endforeach; ?>
      <details class="novo-link">
        <summary>+ Adicionar link</summary>
        <form method="post" action="/setores/<?= $id ?>/links" class="formulario">
          <?= Csrf::campo() ?>
          <label>Nome<input type="text" name="title" required maxlength="160" placeholder="Ex.: Planilha de orçamentos 2027"></label>
          <label>Endereço (link)<input type="url" name="url" required maxlength="2000" placeholder="https://"></label>
          <label>Tipo
            <select name="grp"><?php foreach (Setores::GRUPOS_LINK as $g): ?><option><?= e($g) ?></option><?php endforeach; ?></select>
          </label>
          <div class="acoes"><button class="btn primario" type="submit">Adicionar</button></div>
        </form>
      </details>
    </section>
  </div>
</div>
