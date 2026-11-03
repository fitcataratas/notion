<?php
/** @var array $perfis @var array $pessoas @var string $visao @var array $f @var string $hoje @var string $agrupar @var array $contagem @var int $atrasados @var int $total_geral @var array $linhas */
$volta = url_com([]);
$DN = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
$sel = function (string $campo, string $rotulo, array $itens, bool $id) use ($f): string {
    $h = '<select name="' . $campo . '" aria-label="' . e($rotulo) . '"><option value="">' . e($rotulo) . '</option>';
    foreach ($itens as $i) {
        $v = $id ? (int) $i['id'] : $i;
        $n = $id ? $i['name'] : $i;
        $h .= '<option value="' . e((string) $v) . '"' . ((string) ($f[$campo] ?? '') === (string) $v ? ' selected' : '') . '>' . e($n) . '</option>';
    }
    return $h . '</select>';
};
$cartao = function (array $r, bool $porRede) use ($hoje, $f, $volta): string {
    $atras = Editorial::atrasado($r, $hoje);
    $rotulo = $porRede ? (isset($f['perfil']) ? '' : $r['perfil']) : $r['network'];
    ob_start(); ?>
    <a class="ed-post<?= $atras ? ' atrasado' : '' ?>" style="--c:<?= e(cor_valida($r['perfil_cor'])) ?>" draggable="true"
       data-api="/api/editorial/<?= (int) $r['id'] ?>/data" data-dia="<?= e($r['pub_date']) ?>"
       href="/editorial/<?= (int) $r['id'] ?>?<?= e(http_build_query(['volta' => $volta])) ?>" title="<?= e($r['title']) ?>">
      <?= e($r['title']) ?><?= $r['content'] ? ' ✎' : '' ?>
      <small><?= $rotulo !== '' ? e($rotulo) . ' · ' : '' ?><i class="pt" style="--c:<?= e(Editorial::STATUS[$r['status']] ?? '#999') ?>"></i> <?= e($r['status']) ?><?= $r['dono'] ? ' · ' . e($r['dono']) : '' ?></small>
    </a>
    <?php return ob_get_clean();
};
?>
<div class="barra-visoes">
  <nav class="abas" aria-label="Formas de ver">
    <a href="<?= e(url_com(['view' => 'semana'])) ?>"<?= $visao === 'semana' ? ' aria-current="page"' : '' ?>>Semana</a>
    <a href="<?= e(url_com(['view' => 'lista', 'semana' => null])) ?>"<?= $visao === 'lista' ? ' aria-current="page"' : '' ?>>Lista</a>
  </nav>
  <a class="btn primario" href="/editorial/novo?<?= e(http_build_query(['volta' => $volta] + (isset($f['perfil']) ? ['perfil' => $f['perfil']] : []) + (isset($f['rede']) ? ['rede' => $f['rede']] : []))) ?>">+ Novo conteúdo</a>
  <?php if (Perm::admin(Auth::usuario())): ?><a class="btn texto" href="/admin/editorial">Gerenciar perfis</a><?php endif; ?>
</div>

<div class="resumo-status">
  <?php foreach (Editorial::STATUS as $s => $cor): ?>
    <a class="resumo-item" href="<?= e(url_com(['status' => ($f['status'] ?? '') === $s ? null : $s])) ?>"<?= ($f['status'] ?? '') === $s ? ' aria-current="true"' : '' ?>><span class="chip" style="--c:<?= e($cor) ?>"><?= e($s) ?></span><strong><?= (int) ($contagem[$s] ?? 0) ?></strong></a>
  <?php endforeach; ?>
  <span class="resumo-item<?= $atrasados ? ' alerta-caixa' : '' ?>"><span class="chip" style="--c:#d93a2b">Atrasados</span><strong><?= (int) $atrasados ?></strong></span>
</div>

<form method="get" action="/editorial" class="filtros" role="search">
  <input type="hidden" name="view" value="<?= e($visao) ?>">
  <?php if ($visao === 'semana' && isset($seg)): ?><input type="hidden" name="semana" value="<?= e($seg->format('Y-m-d')) ?>"><?php endif; ?>
  <?= $sel('perfil', 'Todos os perfis', $perfis, true) ?>
  <?= $sel('rede', 'Todas as redes', Editorial::REDES, false) ?>
  <?= $sel('status', 'Todos os status', array_keys(Editorial::STATUS), false) ?>
  <?php if ($visao === 'semana'): ?>
    <select name="agrupar" aria-label="Agrupar linhas"><option value="perfil">Linhas por perfil</option><option value="rede"<?= $agrupar === 'rede' ? ' selected' : '' ?>>Linhas por rede</option></select>
  <?php endif; ?>
  <button class="btn" type="submit">Filtrar</button>
  <?php if ($f): ?><a class="btn texto" href="/editorial?view=<?= e($visao) ?>">Limpar</a><?php endif; ?>
</form>

<?php if ($visao === 'semana'): ?>
  <div class="cal-topo">
    <a class="btn" href="<?= e(url_com(['semana' => $ant])) ?>" aria-label="Semana anterior">←</a>
    <h2><?= e($dias[0]->format('d/m')) ?> a <?= e($dias[6]->format('d/m/Y')) ?></h2>
    <a class="btn" href="<?= e(url_com(['semana' => $prox])) ?>" aria-label="Próxima semana">→</a>
    <a class="btn texto" href="<?= e(url_com(['semana' => null])) ?>">Hoje</a>
  </div>
  <?php
  $porRede = isset($f['perfil']) || $agrupar === 'rede';
  if ($porRede) {
      $grupos = array_map(fn($n) => ['id' => $n, 'nome' => $n, 'cor' => '#8a8a85'], isset($f['rede']) ? [$f['rede']] : Editorial::REDES);
  } else {
      $grupos = array_map(fn($p) => ['id' => $p['id'], 'nome' => $p['name'], 'cor' => $p['color']], array_values(array_filter($perfis, fn($p) => !isset($f['perfil']) || (int) $p['id'] === $f['perfil'])));
  }
  ?>
  <?php if (!$perfis): ?><p class="vazio">Nenhum perfil cadastrado. O administrador cria em “Gerenciar perfis”.</p><?php else: ?>
  <div class="tabela-rolagem">
    <table class="tabela ed-semana">
      <thead><tr><th><?= $porRede ? 'Rede' : 'Perfil' ?></th>
        <?php foreach ($dias as $i => $d): ?><th class="<?= $d->format('Y-m-d') === $hoje ? 'hoje' : '' ?>"><?= $DN[$i] ?> <?= e($d->format('d')) ?></th><?php endforeach; ?>
      </tr></thead>
      <tbody>
      <?php foreach ($grupos as $g): ?>
        <tr>
          <th class="ed-linha" scope="row"><span class="pt" style="--c:<?= e(cor_valida($g['cor'])) ?>"></span> <?= e($g['nome']) ?></th>
          <?php foreach ($dias as $d): $k = $d->format('Y-m-d'); ?>
            <td class="ed-cel" data-dia="<?= e($k) ?>">
              <?php foreach ($linhas as $r) {
                  if ($r['pub_date'] === $k && ($porRede ? $r['network'] === $g['id'] : (int) $r['profile_id'] === (int) $g['id'])) { echo $cartao($r, $porRede); }
              } ?>
              <a class="cal-add" href="/editorial/novo?<?= e(http_build_query(['data' => $k, 'volta' => $volta, ($porRede ? 'rede' : 'perfil') => $g['id']] + (isset($f['perfil']) && $porRede ? ['perfil' => $f['perfil']] : []))) ?>" title="Novo conteúdo neste dia" aria-label="Novo conteúdo em <?= e(data_br($k)) ?>">+</a>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="legenda">Arraste um cartão para outro dia para mudar a data. A cor da barra do cartão é a do perfil; os atrasados têm borda vermelha.</p>
  <?php endif; ?>
<?php else: ?>
  <p class="resumo"><?= count($linhas) ?> conteúdo<?= count($linhas) === 1 ? '' : 's' ?></p>
  <div class="tabela-rolagem">
    <table class="tabela">
      <thead><tr><th>Data</th><th>Perfil</th><th>Rede</th><th>Título</th><th>Formato</th><th>Status</th><th>Responsável</th></tr></thead>
      <tbody>
      <?php foreach ($linhas as $r): ?>
        <tr>
          <td class="nowrap<?= Editorial::atrasado($r, $hoje) ? ' alerta' : '' ?>"><?= e(data_br($r['pub_date'])) ?></td>
          <td class="nowrap"><span class="pt" style="--c:<?= e(cor_valida($r['perfil_cor'])) ?>"></span> <?= e($r['perfil']) ?></td>
          <td><?= e($r['network']) ?></td>
          <td class="col-titulo"><a href="/editorial/<?= (int) $r['id'] ?>?<?= e(http_build_query(['volta' => $volta])) ?>"><strong><?= e($r['title']) ?></strong></a></td>
          <td><?= e($r['format']) ?></td>
          <td>
            <form method="post" action="/editorial/<?= (int) $r['id'] ?>/status" data-auto>
              <?= Csrf::campo() ?><input type="hidden" name="volta" value="<?= e($volta) ?>">
              <select name="status" class="sel-chip" style="--c:<?= e(Editorial::STATUS[$r['status']]) ?>" aria-label="Status de <?= e($r['title']) ?>">
                <?php foreach (array_keys(Editorial::STATUS) as $s): ?><option<?= $s === $r['status'] ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
              </select>
            </form>
          </td>
          <td><?= e($r['dono'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$linhas): ?><tr><td colspan="7" class="vazio-linha"><?= $total_geral ? 'Nenhum conteúdo com esses filtros.' : 'Nenhum conteúdo ainda. Clique em “+ Novo conteúdo”.' ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
