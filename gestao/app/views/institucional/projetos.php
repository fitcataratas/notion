<?php /** @var array $projetos */
$eAdmin = Perm::admin(Auth::usuario());
$principais = array_filter($projetos, fn($p) => $p['is_main']);
$outros = array_filter($projetos, fn($p) => !$p['is_main']);
$cartao = function (array $p) use ($eAdmin): string {
    ob_start(); ?>
    <article class="cartao projeto-card" style="--c:<?= e(cor_valida($p['color'])) ?>">
      <h3><span class="pt" style="--c:<?= e(cor_valida($p['color'])) ?>"></span> <?= e($p['name']) ?></h3>
      <?php if (trim((string) $p['description']) !== ''): ?><p><?= texto_html((string) $p['description']) ?></p><?php endif; ?>
      <p class="suave"><?= (int) $p['tarefas_abertas'] ?> tarefa<?= $p['tarefas_abertas'] == 1 ? '' : 's' ?> em aberto · <?= (int) $p['postagens'] ?> postage<?= $p['postagens'] == 1 ? 'm' : 'ns' ?> · <?= (int) $p['reunioes'] ?> reuni<?= $p['reunioes'] == 1 ? 'ão' : 'ões' ?></p>
      <div class="acoes">
        <a class="btn" href="/tarefas?projeto=<?= (int) $p['id'] ?>">Ver tarefas</a>
        <a class="btn texto" href="/reunioes?projeto=<?= (int) $p['id'] ?>">Reuniões</a>
      </div>
      <?php if ($eAdmin): ?>
      <details class="editar-proj"><summary>Editar descrição</summary>
        <form method="post" action="/admin/projetos/<?= (int) $p['id'] ?>/detalhes" class="formulario">
          <?= Csrf::campo() ?>
          <label>Descrição<textarea name="description" rows="3" maxlength="3000"><?= e((string) $p['description']) ?></textarea></label>
          <label class="inline"><input type="checkbox" name="is_main" value="1"<?= $p['is_main'] ? ' checked' : '' ?>> Evento principal (aparece em destaque)</label>
          <div class="acoes"><button class="btn primario" type="submit">Salvar</button></div>
        </form>
      </details>
      <?php endif; ?>
    </article>
    <?php return ob_get_clean();
};
?>
<?php if ($principais): ?><h2 class="sub-titulo">Eventos principais</h2><div class="grade-pessoas"><?php foreach ($principais as $p) { echo $cartao($p); } ?></div><?php endif; ?>
<?php if ($outros): ?><h2 class="sub-titulo">Outros projetos</h2><div class="grade-pessoas"><?php foreach ($outros as $p) { echo $cartao($p); } ?></div><?php endif; ?>
<?php if (!$projetos): ?><p class="vazio">Nenhum projeto cadastrado.</p><?php endif; ?>
