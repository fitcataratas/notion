<?php /** @var array $eu */ ?>
<section class="cartao estreito">
  <h2>Meu perfil</h2>
  <p class="suave"><?= e($eu['name']) ?> · <?= e($eu['email']) ?> · <?= e(Config::PAPEIS[$eu['role']] ?? '') ?></p>

  <h3>Foto</h3>
  <div class="linha-foto">
    <?= avatar_html($eu, 'grande') ?>
    <div>
      <form method="post" action="/conta/foto" enctype="multipart/form-data" class="formulario">
        <?= Csrf::campo() ?>
        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" required aria-label="Escolher foto">
        <small>JPG, PNG ou WEBP, até 5 MB. O sistema recorta em quadrado e reduz sozinho.</small>
        <div class="acoes"><button class="btn primario" type="submit">Enviar foto</button></div>
      </form>
      <?php if (!empty($eu['photo'])): ?>
        <form method="post" action="/conta/foto/remover" data-confirmar="Remover a sua foto?">
          <?= Csrf::campo() ?><button class="btn" type="submit">Remover foto</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="cartao estreito">
  <h3>Tema</h3>
  <form method="post" action="/conta/tema" class="formulario">
    <?= Csrf::campo() ?>
    <?php foreach (Aparencia::TEMAS as $k => $rot): ?>
      <label class="marca-simples"><input type="radio" name="tema" value="<?= e($k) ?>"<?= ($eu['theme'] ?? 'auto') === $k ? ' checked' : '' ?>> <?= e($rot) ?></label>
    <?php endforeach; ?>
    <div class="acoes"><button class="btn primario" type="submit">Salvar tema</button></div>
  </form>
</section>

<section class="cartao estreito">
  <h3>Senha</h3>
  <p><a class="btn" href="/conta/senha">Trocar a minha senha</a></p>
</section>
