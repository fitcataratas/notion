<?php
/** @var array $minhas @var int $minhas_total @var array $contagem @var array $proximas @var string $datas @var string $hoje */
$u = Auth::usuario();
$primeiro = explode(' ', trim($u['name']))[0];
$hora = (int) date('G');
$saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
$abertas = array_sum(array_intersect_key($contagem, array_flip(Config::STATUS_ABERTOS)));
$atrasadas = count(array_filter($minhas, fn($t) => tarefa_atrasada($t, $hoje)));
$volta = '/';
?>
<section class="boas-vindas">
  <h2><?= e($saudacao) ?>, <?= e($primeiro) ?>!</h2>
  <p>
    <?php if ($abertas === 0): ?>Você não tem tarefas em aberto.
    <?php else: ?>Você tem <strong><?= $abertas ?></strong> tarefa<?= $abertas === 1 ? '' : 's' ?> em aberto<?= $atrasadas ? ', <strong class="alerta">' . $atrasadas . ' com a data vencida</strong>' : '' ?>.<?php endif; ?>
  </p>
</section>

<?php if (trim($datas) !== ''): ?>
  <section class="datas-importantes" aria-label="Datas importantes">
    <h3>Datas importantes</h3>
    <p><?= texto_html($datas) ?></p>
  </section>
<?php endif; ?>

<section class="cartao caixa-pedidos" aria-label="Solicitações de Marketing e Design">
  <div>
    <h3>Solicitações de Marketing e Design</h3>
    <p class="suave">Precisa de uma arte, publicação, material digital, impresso ou apresentação? Preencha o formulário que o Marketing recebe e acompanha.</p>
  </div>
  <a class="btn primario" href="/solicitacoes/nova">Abrir formulário de solicitação</a>
</section>

<div class="resumo-status">
  <?php foreach ($contagem as $s => $n): ?>
    <a class="resumo-item" href="/minhas-tarefas?view=tabela&amp;status=<?= urlencode($s) ?>"><?= chip_status($s) ?><strong><?= (int) $n ?></strong></a>
  <?php endforeach; ?>
</div>

<div class="duas-colunas iguais">
  <section class="cartao">
    <header class="cartao-cab"><h3>Minhas tarefas em aberto</h3><a href="/minhas-tarefas">ver todas</a></header>
    <?php if (!$minhas): ?>
      <p class="suave">Nada por aqui. Quando alguém colocar você como responsável por uma tarefa, ela aparece nesta lista.</p>
    <?php else: ?>
      <ul class="lista-tarefas">
        <?php foreach ($minhas as $t): ?>
          <li>
            <a href="/tarefas/<?= (int) $t['id'] ?>?<?= e(http_build_query(['volta' => $volta])) ?>"><?= e($t['title']) ?></a>
            <span class="meta"><?= chip_status($t['status']) ?><?= chip_prioridade($t['priority']) ?>
              <?php if ($t['start_date']): ?><span class="data<?= tarefa_atrasada($t, $hoje) ? ' alerta' : '' ?>"><?= e(data_tarefa($t)) ?></span><?php endif; ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php if ($minhas_total > count($minhas)): ?><p class="suave">E mais <?= $minhas_total - count($minhas) ?>…</p><?php endif; ?>
    <?php endif; ?>
  </section>

  <section class="cartao">
    <header class="cartao-cab"><h3>Próximos 14 dias</h3><?php if (!Perm::externo($u)): ?><a href="/tarefas?view=calendario">calendário</a><?php endif; ?></header>
    <?php if (!$proximas): ?>
      <p class="suave">Nenhuma tarefa com data nos próximos 14 dias.</p>
    <?php else: ?>
      <ul class="lista-tarefas">
        <?php foreach ($proximas as $t): ?>
          <li>
            <a href="/tarefas/<?= (int) $t['id'] ?>?<?= e(http_build_query(['volta' => $volta])) ?>"><?= e($t['title']) ?></a>
            <span class="meta"><?= chip_status($t['status']) ?><span class="data"><?= e(data_tarefa($t)) ?></span><?= avatares($t['responsaveis']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>
