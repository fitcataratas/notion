<?php /** @var array $erros @var bool $obrigatoria */ ?>
<section class="cartao estreito">
  <h2>Trocar senha</h2>
  <?php if ($obrigatoria): ?>
    <div class="aviso aviso-info">Por segurança, defina uma senha só sua antes de continuar.</div>
  <?php endif; ?>
  <?php foreach ($erros as $e): ?><div class="aviso erro" role="alert"><?= e($e) ?></div><?php endforeach; ?>
  <form method="post" action="/conta/senha" class="formulario">
    <?= Csrf::campo() ?>
    <label>Senha atual
      <input type="password" name="atual" required autocomplete="current-password" maxlength="200">
    </label>
    <label>Nova senha
      <input type="password" name="nova" required autocomplete="new-password" minlength="<?= Config::SENHA_MIN ?>" maxlength="200">
      <small>Pelo menos <?= Config::SENHA_MIN ?> caracteres, com letras e números.</small>
    </label>
    <label>Repita a nova senha
      <input type="password" name="confirma" required autocomplete="new-password" maxlength="200">
    </label>
    <div class="acoes"><button class="btn primario" type="submit">Salvar nova senha</button></div>
  </form>
</section>
