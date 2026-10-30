<?php
/** @var array $a @var array $erros */
$status = ['a-fazer' => 'A fazer', 'em-andamento' => 'Em andamento', 'aguardando' => 'Aguardando', 'concluido' => 'Concluído', 'cancelado' => 'Cancelado'];
$prios = ['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'];
?>
<?php foreach ($erros as $er): ?><div class="aviso erro" role="alert"><?= e($er) ?></div><?php endforeach; ?>

<p class="suave">Escolha as cores e o jeito do sistema. As mudanças valem para todas as pessoas. Você vê um exemplo na hora; só vale de verdade depois de clicar em <strong>Salvar</strong>.</p>

<section class="cartao" id="logo">
  <h3>Logo</h3>
  <div class="linha-foto">
    <?php if ($l = Aparencia::urlLogo()): ?><img class="logo-previa" src="<?= e($l) ?>" alt="Logo atual"><?php else: ?><span class="marca-sinal grande" aria-hidden="true"><?= e(Aparencia::sigla()) ?></span><?php endif; ?>
    <div>
      <form method="post" action="/admin/aparencia/logo" enctype="multipart/form-data" class="formulario">
        <?= Csrf::campo() ?>
        <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" required aria-label="Escolher logo">
        <small>JPG, PNG ou WEBP, até 5 MB. PNG com fundo transparente fica melhor. Sem logo, o sistema mostra a sigla do nome.</small>
        <div class="acoes"><button class="btn" type="submit">Enviar logo</button></div>
      </form>
      <?php if (preg_match('/^[0-9a-f]{24}$/', Aparencia::get('logo'))): ?>
        <form method="post" action="/admin/aparencia/logo/remover" data-confirmar="Remover o logo?"><?= Csrf::campo() ?><button class="btn" type="submit">Remover logo</button></form>
      <?php endif; ?>
    </div>
  </div>
</section>

<form method="post" action="/admin/aparencia" class="formulario" id="form-aparencia">
  <?= Csrf::campo() ?>

  <section class="cartao">
    <h3>Nome</h3>
    <div class="grade-2">
      <label>Nome do sistema<input type="text" name="nome" value="<?= e($a['nome']) ?>" required maxlength="40"></label>
      <label>Subtítulo (embaixo do nome)<input type="text" name="subtitulo" value="<?= e($a['subtitulo']) ?>" maxlength="40"></label>
    </div>
  </section>

  <section class="cartao">
    <h3>Cor principal</h3>
    <p class="suave">Aparece nos botões, no item selecionado do menu e nas fotos sem imagem.</p>
    <div class="linha-cor">
      <input type="color" name="marca" id="cor-marca" value="<?= e($a['marca']) ?>" aria-label="Cor principal">
      <span class="sugestoes">
        <?php foreach (Aparencia::CORES_SUGERIDAS as $c): ?><button type="button" class="amostra" data-cor-alvo="cor-marca" data-cor="<?= e($c) ?>" style="--c:<?= e($c) ?>" title="<?= e($c) ?>"><span class="sr"><?= e($c) ?></span></button><?php endforeach; ?>
      </span>
      <span class="exemplo-marca"><span class="btn primario" id="ex-botao">Botão de exemplo</span></span>
    </div>
  </section>

  <section class="cartao">
    <h3>Cor de cada status</h3>
    <div class="grade-cores">
      <?php foreach ($status as $k => $rot): ?>
        <label class="item-cor"><input type="color" name="status_<?= e($k) ?>" value="<?= e($a['status_' . $k]) ?>" data-previa=".st-<?= e($k) ?>"> <span class="st st-<?= e($k) ?>" data-alvo-previa><?= e($rot) ?></span></label>
      <?php endforeach; ?>
    </div>
    <h3 class="espaco-topo">Cor de cada prioridade</h3>
    <div class="grade-cores">
      <?php foreach ($prios as $k => $rot): ?>
        <label class="item-cor"><input type="color" name="prio_<?= e($k) ?>" value="<?= e($a['prio_' . $k]) ?>" data-previa=".pr-<?= e($k) ?>"> <span class="pr pr-<?= e($k) ?>" data-alvo-previa><?= e($rot) ?></span></label>
      <?php endforeach; ?>
    </div>
    <p class="suave espaco-topo">As cores de cada <a href="/admin/setores">setor</a> e de cada <a href="/admin/projetos">projeto</a> se escolhem nas próprias telas deles.</p>
  </section>

  <section class="cartao">
    <h3>Formato</h3>
    <div class="grade-2">
      <label>Cantos dos quadros e botões
        <select name="cantos"><?php foreach (Aparencia::CANTOS as $k => [$rot]): ?><option value="<?= e($k) ?>"<?= $a['cantos'] === $k ? ' selected' : '' ?>><?= e($rot) ?></option><?php endforeach; ?></select>
      </label>
      <label>Tamanho do texto
        <select name="texto"><?php foreach (Aparencia::TEXTO as $k => [$rot]): ?><option value="<?= e($k) ?>"<?= $a['texto'] === $k ? ' selected' : '' ?>><?= e($rot) ?></option><?php endforeach; ?></select>
      </label>
      <label>Tema padrão <small>(cada pessoa pode escolher o dela em "Meu perfil")</small>
        <select name="tema"><?php foreach (Aparencia::TEMAS as $k => $rot): ?><option value="<?= e($k) ?>"<?= $a['tema'] === $k ? ' selected' : '' ?>><?= e($rot) ?></option><?php endforeach; ?></select>
      </label>
    </div>
  </section>

  <div class="acoes"><button class="btn primario" type="submit">Salvar aparência</button></div>
</form>

<section class="cartao zona-perigo">
  <h3>Voltar ao padrão</h3>
  <p class="suave">Desfaz as cores, o nome e o logo e volta ao visual original verde.</p>
  <form method="post" action="/admin/aparencia/restaurar" data-confirmar="Voltar a aparência para o padrão? As suas escolhas serão perdidas.">
    <?= Csrf::campo() ?><button class="btn perigo" type="submit">Restaurar padrão</button>
  </form>
</section>
