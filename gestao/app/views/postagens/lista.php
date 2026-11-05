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
    <a href="<?= e(url_com(['view' => 'semana', 'pag' => null, 'mes' => null])) ?>"<?= $visao === 'semana' ? ' aria-current="page"' : '' ?>>Dashboard semanal</a>
    <a href="<?= e(url_com(['view' => 'tabela', 'pag' => null, 'mes' => null, 'semana' => null, 'agrupar' => null])) ?>"<?= $visao === 'tabela' ? ' aria-current="page"' : '' ?>>Tabela</a>
    <a href="<?= e(url_com(['view' => 'calendario', 'pag' => null, 'semana' => null, 'agrupar' => null])) ?>"<?= $visao === 'calendario' ? ' aria-current="page"' : '' ?>>Calendário de Postagens</a>
    <a href="/ideias">Ideias de Conteúdo</a>
  </nav>
  <a class="btn primario" href="/postagens/nova?<?= e(http_build_query(['volta' => $volta])) ?>">+ Nova postagem</a>
</div>

<form method="get" action="/postagens" class="filtros" role="search">
  <input type="hidden" name="view" value="<?= e($visao) ?>">
  <?php if ($visao === 'calendario'): ?><input type="hidden" name="mes" value="<?= e($mes) ?>"><?php endif; ?>
  <?php if ($visao === 'semana'): ?><input type="hidden" name="semana" value="<?= e($seg->format('Y-m-d')) ?>"><?php endif; ?>
  <label class="sr" for="f-q">Buscar</label>
  <input id="f-q" type="search" name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="Buscar por título ou conteúdo…" maxlength="100">
  <?= $sel('formato', 'Todos os formatos', $listas['formato']) ?>
  <?= $sel('projeto', 'Todos os projetos', $listas['projeto']) ?>
  <?= $sel('situacao', 'Qualquer situação', $listas['situacao']) ?>
  <?= $sel('tipo', 'Qualquer tipo', $listas['tipo']) ?>
  <?= $sel('plataforma', 'Todas as plataformas', $listas['plataforma']) ?>
  <?= $sel('profissional', 'Todos os profissionais', $pessoas) ?>
  <?php if ($visao === 'tabela'): ?><input type="hidden" name="ordem" value="<?= e($ordem) ?>"><input type="hidden" name="dir" value="<?= e($dir) ?>"><?php endif; ?>
  <?php if ($visao === 'semana'): ?>
    <select name="agrupar" aria-label="Agrupar as linhas"><option value="projeto">Linhas por projeto</option><option value="plataforma"<?= $agrupar === 'plataforma' ? ' selected' : '' ?>>Linhas por plataforma</option></select>
  <?php endif; ?>
  <button class="btn" type="submit">Filtrar</button>
  <?php if ($temFiltro): ?><a class="btn texto" href="<?= e(url($caminho, ['view' => $visao, 'mes' => $visao === 'calendario' ? $mes : null, 'semana' => $visao === 'semana' ? $seg->format('Y-m-d') : null, 'agrupar' => $visao === 'semana' && $agrupar === 'plataforma' ? 'plataforma' : null])) ?>">Limpar</a><?php endif; ?>
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
      <th>Tipo</th><th>Formato</th><th>Objetivo</th><th>Profissional</th><th>Conteúdo</th><th>Plataforma</th><th><?= $cab('situacao', 'Situação do post') ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($linhas as $t): ?>
      <tr>
        <td class="col-titulo"><a href="/postagens/<?= (int) $t['id'] ?>?<?= e(http_build_query(['volta' => $volta])) ?>"><?= e($t['title']) ?></a></td>
        <td class="nowrap"><?= $t['post_date'] ? e(data_br($t['post_date'])) : '<span class="suave">—</span>' ?></td>
        <td><?= $t['projeto'] ? chip($t['projeto'], $t['projeto_cor']) : '' ?></td>
        <td><?= $t['tipo'] ? chip($t['tipo'], $t['tipo_cor']) : '' ?></td>
        <td><div class="chips"><?php foreach ($t['formatos'] as $x): ?><?= chip($x['name'], $x['color']) ?><?php endforeach; ?></div></td>
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

<?php elseif ($visao === 'semana'): ?>
  <?php
  $DN = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
  $porPlat = $agrupar === 'plataforma';
  $linhasGrupo = $porPlat ? $listas['plataforma'] : $listas['projeto'];
  if (isset($f['projeto']) && !$porPlat) { $linhasGrupo = array_values(array_filter($linhasGrupo, fn($g) => (int) $g['id'] === $f['projeto'])); }
  if (isset($f['plataforma']) && $porPlat) { $linhasGrupo = array_values(array_filter($linhasGrupo, fn($g) => (int) $g['id'] === $f['plataforma'])); }
  $semProjeto = !$porPlat && !isset($f['projeto']) ? array_values(array_filter($semana_linhas, fn($r) => !$r['project_id'])) : [];
  $semPlat = $porPlat && !isset($f['plataforma']) ? array_values(array_filter($semana_linhas, fn($r) => !$r['plataformas'])) : [];
  $card = function (array $r) use ($hoje, $volta, $porPlat): string {
      $atras = $r['post_date'] && $r['post_date'] < $hoje && !Postagens::ehPostado($r);
      $cor = cor_valida($r['projeto_cor'] ?? '#8a8a85');
      ob_start(); ?>
      <a class="ed-post<?= $atras ? ' atrasado' : '' ?>" style="--c:<?= e($cor) ?>" draggable="true" data-api="/api/postagens/<?= (int) $r['id'] ?>/data" data-dia="<?= e($r['post_date']) ?>"
         href="/postagens/<?= (int) $r['id'] ?>?<?= e(http_build_query(['volta' => $volta])) ?>" title="<?= e($r['title']) ?>">
        <?= e($r['title']) ?><?= $r['content'] ? ' ✎' : '' ?>
        <small>
          <?= $porPlat ? e($r['projeto'] ?? 'Sem projeto') : e(implode(', ', array_column($r['plataformas'], 'name')) ?: 'Sem plataforma') ?>
          · <i class="pt" style="--c:<?= e(cor_valida($r['situacao_cor'] ?? '#999')) ?>"></i> <?= e($r['situacao'] ?? 'Sem situação') ?>
          <?= $r['formatos'] ? '· ' . e(implode(', ', array_column($r['formatos'], 'name'))) : '' ?>
          <?= $r['pessoas'] ? '· ' . e(implode(', ', array_map(fn($x) => explode(' ', trim($x['name']))[0], $r['pessoas']))) : '' ?>
        </small>
      </a>
      <?php return ob_get_clean();
  };
  $grupos = array_map(fn($g) => ['id' => (int) $g['id'], 'nome' => $g['name'], 'cor' => $g['color']], $linhasGrupo);
  if ($semProjeto) { $grupos[] = ['id' => 0, 'nome' => 'Sem projeto', 'cor' => '#8a8a85']; }
  if ($semPlat) { $grupos[] = ['id' => 0, 'nome' => 'Sem plataforma', 'cor' => '#8a8a85']; }
  ?>
  <div class="resumo-status">
    <?php foreach ($listas['situacao'] as $s): ?>
      <a class="resumo-item" href="<?= e(url_com(['situacao' => ($f['situacao'] ?? 0) === (int) $s['id'] ? null : (int) $s['id']])) ?>"<?= ($f['situacao'] ?? 0) === (int) $s['id'] ? ' aria-current="true"' : '' ?>><?= chip($s['name'], $s['color']) ?><strong><?= (int) ($contagem[(int) $s['id']] ?? 0) ?></strong></a>
    <?php endforeach; ?>
    <span class="resumo-item<?= $atrasados ? ' alerta-caixa' : '' ?>"><?= chip('Atrasados', '#d93a2b') ?><strong><?= (int) $atrasados ?></strong></span>
  </div>
  <div class="cal-topo">
    <a class="btn" href="<?= e(url_com(['semana' => $ant])) ?>" aria-label="Semana anterior">←</a>
    <h2><?= e($dias[0]->format('d/m')) ?> a <?= e($dias[6]->format('d/m/Y')) ?></h2>
    <a class="btn" href="<?= e(url_com(['semana' => $prox])) ?>" aria-label="Próxima semana">→</a>
    <a class="btn texto" href="<?= e(url_com(['semana' => null])) ?>">Hoje</a>
    <?php if ($sem_data_total): ?><a class="btn texto" href="<?= e(url_com(['view' => 'tabela', 'sem_data' => 1, 'semana' => null, 'agrupar' => null])) ?>"><?= (int) $sem_data_total ?> sem data</a><?php endif; ?>
  </div>
  <?php if (!$grupos): ?><p class="vazio">Nenhum <?= $porPlat ? 'plataforma' : 'projeto' ?> cadastrado.</p><?php else: ?>
  <div class="tabela-rolagem">
    <table class="tabela ed-semana">
      <thead><tr><th><?= $porPlat ? 'Plataforma' : 'Projeto' ?></th>
        <?php foreach ($dias as $i => $d): ?><th class="<?= $d->format('Y-m-d') === $hoje ? 'hoje' : '' ?>"><?= $DN[$i] ?> <?= e($d->format('d')) ?></th><?php endforeach; ?>
      </tr></thead>
      <tbody>
      <?php foreach ($grupos as $g): ?>
        <tr>
          <th class="ed-linha" scope="row"><span class="pt" style="--c:<?= e(cor_valida($g['cor'])) ?>"></span> <?= e($g['nome']) ?></th>
          <?php foreach ($dias as $d): $k = $d->format('Y-m-d'); ?>
            <td class="ed-cel" data-dia="<?= e($k) ?>">
              <?php foreach ($semana_linhas as $r) {
                  if ($r['post_date'] !== $k) { continue; }
                  $cai = $porPlat
                      ? ($g['id'] ? in_array($g['id'], array_map('intval', array_column($r['plataformas'], 'id')), true) : !$r['plataformas'])
                      : ($g['id'] ? (int) $r['project_id'] === $g['id'] : !$r['project_id']);
                  if ($cai) { echo $card($r); }
              } ?>
              <a class="cal-add" href="/postagens/nova?<?= e(http_build_query(['data' => $k, 'volta' => $volta] + ($g['id'] ? [($porPlat ? 'plataforma' : 'projeto') => $g['id']] : []))) ?>" title="Nova postagem neste dia" aria-label="Nova postagem em <?= e(data_br($k)) ?>">+</a>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="legenda">Arraste um cartão para outro dia para mudar a data de postagem. A barra colorida do cartão é a cor do projeto; os atrasados têm borda vermelha. Uma postagem com mais de uma plataforma aparece em cada uma quando as linhas são por plataforma.</p>
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
          <a class="evt" draggable="true" data-api="/api/postagens/<?= (int) $t['id'] ?>/data" data-dia="<?= e($ymd) ?>" style="<?= e($corEvt($t['situacao_cor'] ?? '')) ?>" href="/postagens/<?= (int) $t['id'] ?>?<?= e(http_build_query(['volta' => $volta])) ?>" title="<?= e($t['title'] . ' — ' . ($t['situacao'] ?? 'sem situação') . ($t['formatos'] ? ' · ' . implode(', ', array_column($t['formatos'], 'name')) : '')) ?>"><?= e($t['title']) ?><?php if ($t['formatos']): ?> <small>· <?= e(implode(', ', array_column($t['formatos'], 'name'))) ?></small><?php endif; ?></a>
        <?php endforeach; ?>
      </div>
    <?php endfor; ?>
  </div>
  <p class="legenda">Cor do cartão = situação do post: <?php foreach ($listas['situacao'] as $s): ?><?= chip($s['name'], $s['color']) ?> <?php endforeach; ?></p>
<?php endif; ?>
