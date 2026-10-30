<?php /** @var array $t */ ?>
<form method="post" action="/admin/inicio" class="formulario">
  <?= Csrf::campo() ?>
  <section class="cartao estreito">
    <h2>Datas importantes</h2>
    <p class="suave">Aparece em destaque na página inicial de todos. Use para poucas datas realmente institucionais.</p>
    <label>Texto<textarea name="datas_importantes" rows="3" maxlength="2000"><?= e($t['datas_importantes']) ?></textarea></label>
  </section>
  <section class="cartao estreito">
    <h2>Missão, Visão e Valores</h2>
    <label>Missão<textarea name="missao" rows="3" maxlength="2000"><?= e($t['missao']) ?></textarea></label>
    <label>Visão<textarea name="visao" rows="3" maxlength="2000"><?= e($t['visao']) ?></textarea></label>
    <label>Valores<textarea name="valores" rows="5" maxlength="2000"><?= e($t['valores']) ?></textarea></label>
  </section>
  <section class="cartao estreito">
    <h2>Aviso de “Quem faz o quê”</h2>
    <p class="suave">Aparece no topo da página. As pessoas e funções são editadas em <a href="/admin/quem-faz">Quem faz o quê</a>.</p>
    <label>Texto<textarea name="quem_faz_aviso" rows="3" maxlength="1000"><?= e($t['quem_faz_aviso']) ?></textarea></label>
  </section>
  <section class="cartao estreito">
    <h2>Texto de boas-vindas para quem é externo</h2>
    <p class="suave">Aparece na página “Meu espaço” de parceiros e prestadores (como a Vacy).</p>
    <label>Texto<textarea name="externo_intro" rows="4" maxlength="2000"><?= e($t['externo_intro']) ?></textarea></label>
  </section>
  <div class="acoes"><button class="btn primario" type="submit">Salvar tudo</button></div>
</form>
