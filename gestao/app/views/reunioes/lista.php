<?php
/** @var string $visao @var array $f @var array $pessoas @var array $setores @var array $projetos */
$caminho = '/reunioes';
$volta = url_com([]);
$sel = function (string $campo, string $rotulo, array $itens) use ($f): string {
    $h = '<select name="' . $campo . '" aria-label="' . e($rotulo) . '" data-auto-filtro><option value="">' . e($rotulo) . '</option>';
    foreach ($itens as $i) {
        $h .= '<option value="' . (int) $i['id'] . '"' . ((int) ($f[$campo] ?? 0) === (int) $i['id'] ? ' selected' : '') . '>' . e($i['name']) . '</option>';
    }
    return $h . '</select>';
};
$temFiltro = array_diff_key($f, ['tipo' => 1, 'participante' => 1]) || ($visao === 'tabela' && isset($f['tipo']));
$linhaTabela = function (array $l): string {
    ob_start(); ?>
    <tr>
      <td class="col-titulo"><a href="/reunioes/<?= (int) $l['id'] ?>"><strong><?= e($l['title']) ?></strong></a></td>
      <td class="nowrap"><?= $l['meeting_date'] ? e(data_br($l['meeting_date'])) : '<span class="suave">sem data</span>' ?></td>
      <td><?= e($l['kind']) ?></td>
      <td><?php foreach ($l['setores'] as $s): ?><?= chip($s['name'], $s['color']) ?> <?php endforeach; ?></td>
      <td><?= $l['projeto'] ? chip($l['projeto'], $l['projeto_cor']) : '' ?></td>
      <td><?= e($l['ata_nome'] ?? '') ?></td>
      <td><?php if ($l['minutes_url']): ?><a href="<?= e($l['minutes_url']) ?>" target="_blank" rel="noopener noreferrer">Abrir ata ↗</a><?php endif; ?></td>
    </tr>
    <?php return ob_get_clean();
};
$cabeca = '<thead><tr><th>Reunião</th><th>Data</th><th>Tipo</th><th>Setor</th><th>Projeto</th><th>Responsável da ata</th><th>Ata</th></tr></thead>';
?>
<div class="barra-visoes">
  <nav class="abas" aria-label="Formas de ver as reuniões">
    <a href="/reunioes?view=tabela"<?= $visao === 'tabela' ? ' aria-current="page"' : '' ?>>Todas as Atas</a>
    <a href="/reunioes?view=gerais"<?= $visao === 'gerais' ? ' aria-current="page"' : '' ?>>Reuniões Gerais</a>
    <a href="/reunioes?view=calendario"<?= $visao === 'calendario' ? ' aria-current="page"' : '' ?>>Calendário</a>
    <a href="/reunioes?view=setor"<?= $visao === 'setor' ? ' aria-current="page"' : '' ?>>Reunião por Setor</a>
    <a href="/reunioes?view=minhas"<?= $visao === 'minhas' ? ' aria-current="page"' : '' ?>>Minhas reuniões</a>
  </nav>
  <a class="btn primario" href="/reunioes/nova">+ Nova reunião</a>
</div>

<form method="get" action="/reunioes" class="filtros" role="search">
  <input type="hidden" name="view" value="<?= e($visao) ?>">
  <?php if ($visao === 'calendario'): ?><input type="hidden" name="mes" value="<?= e($mes) ?>"><?php endif; ?>
  <label class="sr" for="f-q">Buscar</label>
  <input id="f-q" type="search" name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="Buscar por nome ou anotação…" maxlength="100">
  <?= $sel('setor', 'Todos os setores', $setores) ?>
  <?= $sel('projeto', 'Todos os projetos', $projetos) ?>
  <?php if (!in_array($visao, ['gerais'], true)): ?>
  <select name="tipo" aria-label="Tipo de reunião"><option value="">Qualquer tipo</option>
    <?php foreach (Reunioes::TIPOS as $t): ?><option<?= ($f['tipo'] ?? '') === $t ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
  </select>
  <?php endif; ?>
  <button class="btn" type="submit">Filtrar</button>
  <?php if (!empty($f['q']) || !empty($f['setor']) || !empty($f['projeto']) || ($visao !== 'gerais' && !empty($f['tipo']))): ?><a class="btn texto" href="/reunioes?view=<?= e($visao) ?>">Limpar</a><?php endif; ?>
</form>

<?php if ($visao === 'calendario'): ?>
  <?php $m = (int) $primeiro->format('n'); $hoje = date('Y-m-d'); ?>
  <div class="cal-topo">
    <a class="btn" href="<?= e(url_com(['mes' => $anterior])) ?>" aria-label="Mês anterior">←</a>
    <h2><?= e(ucfirst(MESES_PT[$m])) ?> de <?= e($primeiro->format('Y')) ?></h2>
    <a class="btn" href="<?= e(url_com(['mes' => $proximo])) ?>" aria-label="Próximo mês">→</a>
    <a class="btn texto" href="<?= e(url_com(['mes' => date('Y-m')])) ?>">Hoje</a>
  </div>
  <div class="calendario" role="grid" aria-label="Calendário de reuniões">
    <?php foreach (['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'] as $d): ?><div class="cal-sem" role="columnheader"><?= $d ?></div><?php endforeach; ?>
    <?php for ($d = $ini; $d <= $fim; $d = $d->modify('+1 day')):
        $ymd = $d->format('Y-m-d'); $doMes = $d->format('Y-m') === $mes; ?>
      <div class="cal-dia<?= $doMes ? '' : ' fora' ?><?= $ymd === $hoje ? ' hoje' : '' ?>" role="gridcell" data-dia="<?= e($ymd) ?>">
        <div class="cal-num">
          <span><?= (int) $d->format('j') ?><?= $d->format('j') === '1' ? ' ' . e(mb_substr(MESES_PT[(int) $d->format('n')], 0, 3)) : '' ?></span>
          <a class="cal-add" href="/reunioes/nova?<?= e(http_build_query(['data' => $ymd])) ?>" title="Nova reunião neste dia" aria-label="Nova reunião em <?= e(data_br($ymd)) ?>">+</a>
        </div>
        <?php foreach ($por_dia[$ymd] ?? [] as $t): ?>
          <a class="evt" draggable="true" data-api="/api/reunioes/<?= (int) $t['id'] ?>/data" data-dia="<?= e($ymd) ?>" style="--c:<?= e(cor_valida($t['projeto_cor'] ?? '#6b7280')) ?>" href="/reunioes/<?= (int) $t['id'] ?>" title="<?= e($t['title']) ?>"><?= e($t['title']) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endfor; ?>
  </div>
<?php elseif ($visao === 'setor'): ?>
  <?php if (!$grupos && !$sem_setor): ?><p class="vazio">Nenhuma reunião encontrada.</p><?php endif; ?>
  <?php foreach ($grupos as $g): ?>
    <section class="cartao grupo-reuniao">
      <h3><span class="pt" style="--c:<?= e($g['setor']['color']) ?>"></span> <?= e($g['setor']['name']) ?> <small class="suave">(<?= count($g['reunioes']) ?>)</small></h3>
      <div class="tabela-rolagem"><table class="tabela"><?= $cabeca ?><tbody><?php foreach ($g['reunioes'] as $l) { echo $linhaTabela($l); } ?></tbody></table></div>
    </section>
  <?php endforeach; ?>
  <?php if ($sem_setor): ?>
    <section class="cartao grupo-reuniao">
      <h3>Sem setor <small class="suave">(<?= count($sem_setor) ?>)</small></h3>
      <div class="tabela-rolagem"><table class="tabela"><?= $cabeca ?><tbody><?php foreach ($sem_setor as $l) { echo $linhaTabela($l); } ?></tbody></table></div>
    </section>
  <?php endif; ?>
<?php else: ?>
  <p class="resumo"><?= count($linhas) ?> <?= count($linhas) === 1 ? 'reunião' : 'reuniões' ?><?= count($linhas) >= 1000 ? ' (mostrando as 1000 mais recentes)' : '' ?></p>
  <div class="tabela-rolagem">
    <table class="tabela"><?= $cabeca ?>
      <tbody>
        <?php foreach ($linhas as $l) { echo $linhaTabela($l); } ?>
        <?php if (!$linhas): ?><tr><td colspan="7" class="vazio-linha"><?= $visao === 'minhas' ? 'Você ainda não está em nenhuma reunião registrada.' : 'Nenhuma reunião encontrada.' ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
