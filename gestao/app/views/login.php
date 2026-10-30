<?php /** @var string|null $erro @var string $email @var string $volta */ ?>
<!doctype html>
<html lang="pt-BR"<?= tema_atual(null) ? ' data-tema="' . e(tema_atual(null)) . '"' : '' ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Entrar · <?= e(Aparencia::nome()) ?></title>
<link rel="stylesheet" href="/assets/app.css?v=<?= filemtime(APP_ROOT . '/public/assets/app.css') ?>">
<?= tema_link() ?>
</head>
<body class="pagina-login">
<main class="login">
  <div class="login-marca">
    <?php if ($logoUrl = Aparencia::urlLogo()): ?>
      <img src="<?= e($logoUrl) ?>" alt="<?= e(Aparencia::nome()) ?>">
    <?php else: ?>
      <span class="marca-sinal grande" aria-hidden="true"><?= e(Aparencia::sigla()) ?></span>
    <?php endif; ?>
    <h1><?= e(Aparencia::nome()) ?></h1>
    <p>Setores, tarefas e demandas da equipe.</p>
  </div>
  <form method="post" action="/login" class="cartao login-form" autocomplete="on">
    <?= Csrf::campo() ?>
    <input type="hidden" name="volta" value="<?= e($volta) ?>">
    <?php if ($erro): ?><div class="aviso erro" role="alert"><?= e($erro) ?></div><?php endif; ?>
    <label>E-mail
      <input type="email" name="email" value="<?= e($email) ?>" required autofocus autocomplete="username" maxlength="190">
    </label>
    <label>Senha
      <input type="password" name="senha" required autocomplete="current-password" maxlength="200">
    </label>
    <button class="btn primario grande" type="submit">Entrar</button>
    <p class="ajuda">Esqueceu a senha? Peça uma nova ao administrador do sistema.</p>
  </form>
</main>
</body>
</html>
