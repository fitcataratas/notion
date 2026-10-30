<?php /** @var string $titulo @var string $mensagem */ ?>
<?php if (!Auth::usuario()): ?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?></title><link rel="stylesheet" href="/assets/app.css"></head><body class="pagina-login"><main class="login">
<?php endif; ?>
<section class="cartao vazio">
  <h2><?= e($titulo) ?></h2>
  <p><?= e($mensagem) ?></p>
  <p><a class="btn" href="/">Voltar ao início</a></p>
</section>
<?php if (!Auth::usuario()): ?></main></body></html><?php endif; ?>
