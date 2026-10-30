<?php
/** @var array $f @var string $visao @var array $listas @var array $pessoas */
$caminho = '/postagens';
$hoje = date('Y-m-d');
$volta = $_SERVER['REQUEST_URI'] ?? '/postagens';
$temFiltro = count(array_diff_key($f, ['sem_data' => 1])) > 0;
$sel = function (string $nome, string $vazio, array $itens) use ($f): string {
    $h = '<select name="' . e($nome) . '" aria-label="' . e($vazio) . '"><option value="">' . e($vazio) . '</option>';
    foreach ($itens as $i) {
        $h .= '<option value="' . (int) $i['id'] . '"' . (($f[$nome] ?? 0) === (int) $i['id'] ? ' selected' : '') . '>' . e($i['name']) . '</option>';
    }
    return $h . '</select>';
};
$corEvt = function (string $c): string {
    $c = cor_valida($c);
    return '--l:' . $c . ';--b:color-mix(in srgb,' . $c . ' 16%,var(--superficie));--t:color-mix(in srgb,' . $c . ' 70%,var(--texto))';
};
?>
<div class="barra-visoes">
  <nav class="abas" aria-label="Formas de ver o cronograma">
    <a href="<?= e(url_com(['view' => 'tabela', 'pag' => null, 'mes' => null])) ?>"<?= $visao === 'tabela' ? ' aria-current="page"' : '' ?>>Tabela</a>
    <a href="<?= e(url_com(['view' => 'calendario', 'pag' => null])) ?>"<?= $visao === 'calendario' ? ' aria-current="page"' : '' ?>>Calendário de Postagens</a>
    <a href="/ideias">Ideias de Conteúdo</a>
  </nav>
  <a class="btn primario" href="/postagens/nova?<?= e(http_build_query(['volta' => $volta])) ?>">+ Nova postagem</a>
</div>

<form method="get" action="/postagens" class="filtros" role="search">
  <input type="hidden" name="view" value="<?= e($visao) ?>">
  <?php if ($visao === 'calendario'): ?><input type="hidden" name="mes" value="<?= e($mes) ?>"><?php endif; ?>
  <label class="sr" for="f-q">Buscar</label>
  <input id="f-q" type="search" name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="Buscar por título ou conteúdo…" maxlength="100">
  <?= $sel('formato', 'Todos os formatos', $listas['formato']) ?>
  <?= $sel('projeto', 'Todos os projetos', $listas['projeto']) ?>
  <?= $sel('situacao', 'Qualquer situação', $listas['situacao']) ?>
  <?= $sel('tipo', 'Qualquer tipo', $listas['tipo']) ?>
  <?= $sel('plataforma', 'Todas as plataformas', $listas['plataforma']) ?>
  <?= $sel('profissional', 'Todos os profissionais', $pessoas) ?>
  <?php if ($visao === 'tabela'): ?><input type="hidden" name="ordem" value="<?= e($ordem) ?>"><input type="hidden" name="dir" value="<?= e($dir) ?>"><?php endif; ?>
  <button class="btn" type="submit">Filtrar</button>
  <?php if ($temFiltro): ?><a class="btn texto" href="<?= e(url($caminho, ['view' => $visao, 'mes' => $visao === 'calendario' ? $mes : null])) ?>">Limpar</a><?php endif; ?>
</form>

<?php if ($visao === 'tabela'): ?>
  <?php
  $cab = function (string $chave, string $rotulo) use ($ordem, $dir): string {
      $novo = ($ordem === $chave && $dir === 'asc') ? 'desc' : 'asc';
      $seta = $ordem === $chave ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
      return '<a href="' . e(url_com(['ordem' => $chave, 'dir' => $novo, 'pag' => null])) . '">' . e($rotulo) . $seta . '</a>';
  };
  ?>
  <p class="resumo"><?= (int) $total ?> postage<?= $total === 1 ? 'm' : 'ns' ?><?= $temFiltro ? ' com esses filtros' : '' ?>.</p>
  <div class="tabela-rolagem">
  <table class="tabela tabela-posts">
    <thead><tr>
      <th><?= $cab('titulo', 'Título') ?></th><th><?= $cab('data', 'Data de Postagem') ?></th><th><?= $cab('projeto', 'Projeto') ?></th>
      <th>Tipo</th><th><?= $cab('formato', 'Formato') ?></th><th>Objetivo</th><th>Profissional</th><th>Conteúdo</th><th>Plataforma</th><th><?= $cab('situacao', 'Situação do post') ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($linhas as $t): ?>
      <tr>
        <td class="col-titulo"><a href="/postagens/<?= (int) $t['id'] ?>?<?= e(http_build_query(['volta' => $volta])) ?>"><?= e($t['title']) ?></a></td>
        <td class="nowrap"><?= $t['post_date'] ? e(data_br($t['post_date'])) : '<span class="suave">—</span>' ?></td>
        <td><?= $t['projeto'] ? chip($t['projeto'], $t['projeto_cor']) : '' ?></td>
        <td><?= $t['tipo'] ? chip($t['tipo'], $t['tipo_cor']) : '' ?></td>
        <td><?= $t['formato'] ? chip($t['formato'], $t['formato_cor']) : '' ?></td>
        <td><?= e($t['objective'] ?? '') ?></td>
        <td><?php foreach ($t['pessoas'] as $pp): ?><span class="pessoa"><?= avatar_html($pp, 'peq') ?><?= e($pp['name']) ?></span> <?php endforeach; ?></td>
        <td class="col-resumo"><?= e(mb_strimwidth((string) $t['content'], 0, 70, '…')) ?></td>
        <td><div class="chips"><?php foreach ($t['plataformas'] as $x): ?><?= chip($x['name'], $x['color']) ?><?php endforeach; ?></div></td>
        <td>
          <form method="post" action="/postagens/<?= (int) $t['id'] ?>/situacao" data-auto>
            <?= Csrf::campo() ?><input type="hidden" name="volta" value="<?= e($volta) ?>">
            <select name="status_id" class="sel-chip" style="--c:<?= e(cor_valida($t['situacao_cor'] ?? '')) ?>" aria-label="Situação de <?= e($t['title']) ?>">
              <option value=""></option>
              <?php foreach ($listas['situacao'] as $s): ?><option value="<?= (int) $s['id'] ?>"<?= (int) $t['status_id'] === (int) $s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
            </select>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$linhas): ?><tr><td colspan="10" class="vazio-linha">Nenhuma postagem encontrada.</td></tr><?php endif; ?>
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
    <?php if ($sem_data_total): ?><a class="btn texto" href="<?= e(url_com(['view' => 'tabela', 'sem_data' => 1, 'mes' => null])) ?>"><?= (int) $sem_data_total ?> sem data</a><?php endif; ?>
  </div>
  <div class="calendario" role="grid" aria-label="Calendário de postagens">
    <?php foreach (['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'] as $d): ?><div class="cal-sem" role="columnheader"><?= $d ?></div><?php endforeach; ?>
    <?php for ($d = $ini; $d <= $fim; $d = $d->modify('+1 day')):
        $ymd = $d->format('Y-m-d');
        $doMes = $d->format('Y-m') === $mes;
        $evts = $por_dia[$ymd] ?? [];
        ?>
      <div class="cal-dia<?= $doMes ? '' : ' fora' ?><?= $ymd === $hoje ? ' hoje' : '' ?>" role="gridcell" data-dia="<?= e($ymd) ?>">
        <div class="cal-num">
          <span><?= (int) $d->format('j') ?><?= $d->format('j') === '1' ? ' ' . e(mb_substr(MESES_PT[(int) $d->format('n')], 0, 3)) : '' ?></span>
          <a class="cal-add" href="/postagens/nova?<?= e(http_build_query(['data' => $ymd, 'volta' => $volta])) ?>" title="Nova postagem neste dia" aria-label="Nova postagem em <?= e(data_br($ymd)) ?>">+</a>
        </div>
        <?php foreach ($evts as $t): ?>
          <a class="evt" draggable="true" data-api="/api/postagens/<?= (int) $t['id'] ?>/data" data-dia="<?= e($ymd) ?>" style="<?= e($corEvt($t['situacao_cor'] ?? '')) ?>" href="/postagens/<?= (int) $t['id'] ?>?<?= e(http_build_query(['volta' => $volta])) ?>" title="<?= e($t['title'] . ' — ' . ($t['situacao'] ?? 'sem situação') . ($t['formato'] ? ' · ' . $t['formato'] : '')) ?>"><?= e($t['title']) ?><?php if ($t['formato']): ?> <small>· <?= e($t['formato']) ?></small><?php endif; ?></a>
        <?php endforeach; ?>
      </div>
    <?php endfor; ?>
  </div>
  <p class="legenda">Cor do cartão = situação do post: <?php foreach ($listas['situacao'] as $s): ?><?= chip($s['name'], $s['color']) ?> <?php endforeach; ?></p>
<?php endif; ?>
