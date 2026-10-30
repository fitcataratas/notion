<?php
/**
 * @var array $f filtros  @var string $visao  @var array $setores @var array $projetos @var array $pessoas
 * @var bool $so_minhas
 */
$caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$hoje = date('Y-m-d');
$ehExterno = Perm::externo(Auth::usuario());
$temFiltro = $f['q'] || $f['status'] || $f['prioridade'] || $f['tipo'] || $f['setor'] || $f['projeto'] || $f['responsavel'] || (!$so_minhas && $f['mine']);
$volta = $_SERVER['REQUEST_URI'] ?? '/tarefas';
?>
<?php if (!$ehExterno && $f['setor'] && ($setorAtual = Setores::obter((int) $f['setor']))): ?>
  <p class="suave"><a href="/setores/<?= (int) $setorAtual['id'] ?>">← Página do setor <strong><?= e($setorAtual['name']) ?></strong></a></p>
<?php endif; ?>
<div class="barra-visoes">
  <nav class="abas" aria-label="Formas de ver as tarefas">
    <a href="<?= e(url_com(['view' => 'quadro', 'pag' => null, 'mes' => null])) ?>"<?= $visao === 'quadro' ? ' aria-current="page"' : '' ?>>Quadro</a>
    <a href="<?= e(url_com(['view' => 'tabela', 'pag' => null, 'mes' => null])) ?>"<?= $visao === 'tabela' ? ' aria-current="page"' : '' ?>>Tabela</a>
    <a href="<?= e(url_com(['view' => 'calendario', 'pag' => null])) ?>"<?= $visao === 'calendario' ? ' aria-current="page"' : '' ?>>Calendário</a>
  </nav>
</div>

<form method="get" action="<?= e($caminho) ?>" class="filtros" role="search">
  <input type="hidden" name="view" value="<?= e($visao) ?>">
  <?php if ($visao === 'calendario'): ?><input type="hidden" name="mes" value="<?= e($mes) ?>"><?php endif; ?>
  <label class="sr" for="f-q">Buscar</label>
  <input id="f-q" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Buscar por atividade ou briefing…" maxlength="100">
  <?php if (!$ehExterno): ?>
    <select name="setor" aria-label="Setor"><option value="">Todos os setores</option>
      <?php foreach ($setores as $s): ?><option value="<?= (int) $s['id'] ?>"<?= $f['setor'] === (int) $s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
    </select>
    <select name="projeto" aria-label="Projeto"><option value="">Todos os projetos</option>
      <?php foreach ($projetos as $s): ?><option value="<?= (int) $s['id'] ?>"<?= $f['projeto'] === (int) $s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
    </select>
    <?php if (!$so_minhas): ?>
    <select name="responsavel" aria-label="Responsável"><option value="">Todos os responsáveis</option>
      <?php foreach ($pessoas as $s): ?><option value="<?= (int) $s['id'] ?>"<?= $f['responsavel'] === (int) $s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
    </select>
    <?php endif; ?>
  <?php endif; ?>
  <select name="status" aria-label="Status"><option value="">Todos os status</option>
    <option value="abertas"<?= $f['status'] === 'abertas' ? ' selected' : '' ?>>Em aberto (sem concluídas)</option>
    <?php foreach (Config::STATUS as $s): ?><option<?= $f['status'] === $s ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
  </select>
  <select name="prioridade" aria-label="Prioridade"><option value="">Qualquer prioridade</option>
    <?php foreach (Config::PRIORIDADES as $s): ?><option<?= $f['prioridade'] === $s ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
  </select>
  <select name="tipo" aria-label="Tipo"><option value="">Qualquer tipo</option>
    <?php foreach (Config::TIPOS as $s): ?><option<?= $f['tipo'] === $s ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
  </select>
  <?php if ($visao === 'tabela'): ?>
    <input type="hidden" name="ordem" value="<?= e($ordem) ?>"><input type="hidden" name="dir" value="<?= e($dir) ?>">
  <?php endif; ?>
  <button class="btn" type="submit">Filtrar</button>
  <?php if ($temFiltro): ?><a class="btn texto" href="<?= e(url($caminho, ['view' => $visao, 'mes' => $visao === 'calendario' ? $mes : null])) ?>">Limpar</a><?php endif; ?>
</form>

<?php if ($visao === 'quadro'): ?>
  <p class="resumo"><?= (int) $total ?> tarefa<?= $total === 1 ? '' : 's' ?><?= $temFiltro ? ' com esses filtros' : '' ?>. Arraste os cartões entre as colunas para mudar o status.</p>
  <div class="quadro" data-api="/api/tarefas">
    <?php foreach ($colunas as $status => $tarefas): ?>
      <section class="coluna" data-status="<?= e($status) ?>" aria-label="<?= e($status) ?>">
        <header><?= chip_status($status) ?><span class="cont"><?= count($tarefas) ?></span></header>
        <div class="coluna-corpo">
          <?php foreach ($tarefas as $t): ?>
            <article class="cartao-tarefa<?= tarefa_atrasada($t, $hoje) ? ' atrasada' : '' ?>" draggable="true" data-id="<?= (int) $t['id'] ?>">
              <a class="cartao-titulo" href="/tarefas/<?= (int) $t['id'] ?>?<?= e(http_build_query(['volta' => $volta])) ?>"><?= e($t['title']) ?></a>
              <?php if ($t['setores'] || $t['projetos']): ?>
                <div class="chips">
                  <?php foreach ($t['projetos'] as $x): ?><?= chip($x['name'], $x['color']) ?><?php endforeach; ?>
                  <?php foreach ($t['setores'] as $x): ?><?= chip($x['name'], $x['color']) ?><?php endforeach; ?>
                </div>
              <?php endif; ?>
              <div class="meta">
                <?= chip_prioridade($t['priority']) ?>
                <?php if ($t['start_date']): ?><span class="data<?= tarefa_atrasada($t, $hoje) ? ' alerta' : '' ?>" title="Data"><?= e(data_tarefa($t)) ?></span><?php endif; ?>
                <?= avatares($t['responsaveis']) ?>
              </div>
              <select class="mover" aria-label="Mover “<?= e($t['title']) ?>” para outro status">
                <option value="">Mover para…</option>
                <?php foreach (Config::STATUS as $s): if ($s !== $status): ?><option><?= e($s) ?></option><?php endif; endforeach; ?>
              </select>
            </article>
          <?php endforeach; ?>
          <?php if (!$tarefas): ?><p class="coluna-vazia">Nenhuma tarefa</p><?php endif; ?>
        </div>
      </section>
    <?php endforeach; ?>
  </div>

<?php elseif ($visao === 'tabela'): ?>
  <?php
  $cab = function (string $chave, string $rotulo) use ($ordem, $dir): string {
      $novo = ($ordem === $chave && $dir === 'asc') ? 'desc' : 'asc';
      $seta = $ordem === $chave ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
      return '<a href="' . e(url_com(['ordem' => $chave, 'dir' => $novo, 'pag' => null])) . '">' . e($rotulo) . $seta . '</a>';
  };
  ?>
  <p class="resumo"><?= (int) $total ?> tarefa<?= $total === 1 ? '' : 's' ?><?= $temFiltro ? ' com esses filtros' : '' ?>.</p>
  <div class="tabela-rolagem">
  <table class="tabela">
    <thead><tr>
      <th><?= $cab('atividade', 'Atividade') ?></th><th><?= $cab('status', 'Status') ?></th><th><?= $cab('data', 'Data') ?></th>
      <th>Setor</th><th>Responsável</th><th>Projeto</th><th>Tipo</th><th><?= $cab('prioridade', 'Prioridade') ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($linhas as $t): ?>
      <tr>
        <td class="col-titulo"><a href="/tarefas/<?= (int) $t['id'] ?>?<?= e(http_build_query(['volta' => $volta])) ?>"><?= e($t['title']) ?></a></td>
        <td><?= chip_status($t['status']) ?></td>
        <td class="nowrap<?= tarefa_atrasada($t, $hoje) ? ' alerta' : '' ?>"><?= e(data_tarefa($t)) ?></td>
        <td><?php foreach ($t['setores'] as $x): ?><?= chip($x['name'], $x['color']) ?> <?php endforeach; ?></td>
        <td><?php foreach ($t['responsaveis'] as $pp): ?><span class="pessoa"><?= avatar_html($pp, 'peq') ?><?= e($pp['name']) ?></span> <?php endforeach; ?></td>
        <td><?php foreach ($t['projetos'] as $x): ?><?= chip($x['name'], $x['color']) ?> <?php endforeach; ?></td>
        <td><?= e($t['type']) ?></td>
        <td><?= chip_prioridade($t['priority']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$linhas): ?><tr><td colspan="8" class="vazio-linha">Nenhuma tarefa encontrada.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
  <?php if ($paginas > 1): ?>
    <nav class="paginacao" aria-label="Páginas">
      <?php if ($pag > 1): ?><a class="btn" href="<?= e(url_com(['pag' => $pag - 1])) ?>">← Anterior</a><?php endif; ?>
      <span>Página <?= $pag ?> de <?= $paginas ?></span>
      <?php if ($pag < $paginas): ?><a class="btn" href="<?= e(url_com(['pag' => $pag + 1])) ?>">Próxima →</a><?php endif; ?>
    </nav>
  <?php endif; ?>

<?php else: ?>
  <?php $m = (int) $primeiro->format('n'); ?>
  <div class="cal-topo">
    <a class="btn" href="<?= e(url_com(['mes' => $anterior])) ?>" aria-label="Mês anterior">←</a>
    <h2><?= e(ucfirst(MESES_PT[$m])) ?> de <?= e($primeiro->format('Y')) ?></h2>
    <a class="btn" href="<?= e(url_com(['mes' => $proximo])) ?>" aria-label="Próximo mês">→</a>
    <a class="btn texto" href="<?= e(url_com(['mes' => date('Y-m')])) ?>">Hoje</a>
    <?php if ($sem_data_total): ?>
      <a class="btn texto" href="<?= e(url_com(['view' => 'tabela', 'sem_data' => 1, 'mes' => null])) ?>"><?= (int) $sem_data_total ?> sem data</a>
    <?php endif; ?>
  </div>
  <div class="calendario" role="grid" aria-label="Calendário">
    <?php foreach (['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'] as $d): ?><div class="cal-sem" role="columnheader"><?= $d ?></div><?php endforeach; ?>
    <?php for ($d = $ini; $d <= $fim; $d = $d->modify('+1 day')):
        $ymd = $d->format('Y-m-d');
        $doMes = $d->format('Y-m') === $mes;
        $evts = $por_dia[$ymd] ?? [];
        ?>
      <div class="cal-dia<?= $doMes ? '' : ' fora' ?><?= $ymd === $hoje ? ' hoje' : '' ?>" role="gridcell" data-dia="<?= e($ymd) ?>">
        <div class="cal-num">
          <span><?= (int) $d->format('j') ?><?= $d->format('j') === '1' ? ' ' . e(mb_substr(MESES_PT[(int) $d->format('n')], 0, 3)) : '' ?></span>
          <?php if (!$ehExterno): ?><a class="cal-add" href="/tarefas/nova?<?= e(http_build_query(['data' => $ymd, 'volta' => $volta])) ?>" title="Nova tarefa neste dia" aria-label="Nova tarefa em <?= e(data_br($ymd)) ?>">+</a><?php endif; ?>
        </div>
        <?php foreach (array_slice($evts, 0, 3) as $t): ?>
          <a class="evt st-<?= e(slug($t['status'])) ?>"<?= $ehExterno ? '' : ' draggable="true" data-api="/api/tarefas/' . (int) $t['id'] . '/data" data-dia="' . e($ymd) . '"' ?> href="/tarefas/<?= (int) $t['id'] ?>?<?= e(http_build_query(['volta' => $volta])) ?>" title="<?= e($t['title'] . ' — ' . $t['status']) ?>"><?= $t['start_time'] && $t['start_date'] === $ymd ? e(hora_curta($t['start_time'])) . ' ' : '' ?><?= e($t['title']) ?></a>
        <?php endforeach; ?>
        <?php if (count($evts) > 3): ?>
          <details class="cal-mais"><summary>+<?= count($evts) - 3 ?> mais</summary>
            <?php foreach (array_slice($evts, 3) as $t): ?>
              <a class="evt st-<?= e(slug($t['status'])) ?>"<?= $ehExterno ? '' : ' draggable="true" data-api="/api/tarefas/' . (int) $t['id'] . '/data" data-dia="' . e($ymd) . '"' ?> href="/tarefas/<?= (int) $t['id'] ?>?<?= e(http_build_query(['volta' => $volta])) ?>" title="<?= e($t['title'] . ' — ' . $t['status']) ?>"><?= e($t['title']) ?></a>
            <?php endforeach; ?>
          </details>
        <?php endif; ?>
      </div>
    <?php endfor; ?>
  </div>
  <p class="legenda">
    <?php foreach (Config::STATUS as $s): ?><?= chip_status($s) ?> <?php endforeach; ?>
  </p>
<?php endif; ?>
