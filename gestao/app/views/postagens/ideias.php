<?php /** @var array $ideias @var bool $todas */ ?>
<div class="barra-visoes">
  <nav class="abas" aria-label="Cronograma">
    <a href="/postagens?view=semana">Dashboard semanal</a>
    <a href="/postagens?view=tabela">Tabela</a>
    <a href="/postagens?view=calendario">Calendário de Postagens</a>
    <a href="/ideias" aria-current="page">Ideias de Conteúdo</a>
  </nav>
</div>
<p class="suave">Espaço para registrar ideias de conteúdo que podem ser desenvolvidas no futuro. Quando uma ideia entrar no planejamento, use “Virar postagem”: ela vai para o Cronograma de Postagens.</p>

<section class="cartao estreito">
  <h3>Nova ideia</h3>
  <form method="post" action="/ideias" class="formulario">
    <?= Csrf::campo() ?>
    <label>Ideia<input type="text" name="title" required maxlength="255" placeholder="Qual é a ideia?"></label>
    <label>Detalhes <small>(opcional)</small><textarea name="notes" rows="3" maxlength="10000"></textarea></label>
    <div class="acoes"><button class="btn primario" type="submit">Guardar ideia</button></div>
  </form>
</section>

<p class="resumo"><a href="<?= e(url_com(['todas' => $todas ? null : 1])) ?>"><?= $todas ? 'Esconder as já usadas' : 'Mostrar também as já usadas' ?></a></p>
<div class="tabela-rolagem">
<table class="tabela">
  <thead><tr><th>Ideia</th><th>Registrada em</th><th>Por</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($ideias as $i): ?>
    <tr<?= $i['used'] ? ' class="usada"' : '' ?>>
      <td class="col-titulo"><strong><?= e($i['title']) ?></strong><?php if ($i['notes']): ?><br><span class="suave"><?= e(mb_strimwidth((string) $i['notes'], 0, 160, '…')) ?></span><?php endif; ?></td>
      <td class="nowrap"><?= e(datahora_br($i['created_at'])) ?></td>
      <td><?= e($i['autor'] ?? '') ?></td>
      <td class="nowrap">
        <?php if ($i['used']): ?>
          <?php if ($i['post_id']): ?><a class="btn texto" href="/postagens/<?= (int) $i['post_id'] ?>">Já virou postagem</a><?php else: ?><span class="suave">Usada</span><?php endif; ?>
        <?php else: ?>
          <a class="btn" href="/postagens/nova?ideia=<?= (int) $i['id'] ?>">Virar postagem</a>
        <?php endif; ?>
        <form method="post" action="/ideias/<?= (int) $i['id'] ?>/excluir" class="em-linha" data-confirmar="Apagar esta ideia?"><?= Csrf::campo() ?><button class="btn perigo texto" type="submit">Apagar</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$ideias): ?><tr><td colspan="4" class="vazio-linha">Nenhuma ideia guardada.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
