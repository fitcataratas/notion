<?php
/** @var string $titulo @var string $conteudo @var array|null $usuario */
$caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$visao = $_GET['view'] ?? 'quadro';
$eAdmin = $usuario && Perm::admin($usuario);
$eExterno = $usuario && Perm::externo($usuario);
$ativo = function (string $prefixo, bool $exato = false) use ($caminho): string {
    return ($exato ? $caminho === $prefixo : str_starts_with($caminho, $prefixo)) ? ' aria-current="page"' : '';
};
$logo = Aparencia::urlLogo();
$temaAtual = tema_atual($usuario);
$naoLidas = $usuario ? Notificacoes::naoLidas((int) $usuario['id']) : 0;
$recentes = $usuario ? Notificacoes::recentes((int) $usuario['id'], 8) : [];
?>
<!doctype html>
<html lang="pt-BR"<?= $temaAtual ? ' data-tema="' . e($temaAtual) . '"' : '' ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
<title><?= e($titulo) ?> · <?= e(Aparencia::nome()) ?></title>
<link rel="stylesheet" href="/assets/app.css?v=<?= filemtime(APP_ROOT . '/public/assets/app.css') ?>">
<?= tema_link() ?>
<script src="/assets/app.js?v=<?= filemtime(APP_ROOT . '/public/assets/app.js') ?>" defer></script>
</head>
<body>
<a class="pular" href="#principal">Pular para o conteúdo</a>
<input type="checkbox" id="menu-toggle" class="menu-toggle" aria-label="Abrir menu">
<div class="app">
  <aside class="lateral" aria-label="Menu principal">
    <a class="marca" href="/">
      <?php if ($logo): ?><img src="<?= e($logo) ?>" alt="<?= e(Aparencia::nome()) ?>"><?php else: ?>
        <span class="marca-sinal" aria-hidden="true"><?= e(Aparencia::sigla()) ?></span>
        <span class="marca-texto"><strong><?= e(Aparencia::nome()) ?></strong><small><?= e(Aparencia::get('subtitulo')) ?></small></span>
      <?php endif; ?>
    </a>
    <nav>
      <a href="/"<?= $ativo('/', true) ?>><span class="ico">⌂</span> Início</a>
      <a href="/minhas-tarefas"<?= $ativo('/minhas-tarefas') ?>><span class="ico">✓</span> Minhas tarefas</a>
      <?php if ($eExterno): ?>
        <a href="/espaco"<?= $ativo('/espaco') ?>><span class="ico">◈</span> Meu espaço</a>
        <a href="/solicitacoes"<?= $ativo('/solicitacoes') ?>><span class="ico">✉</span> Meus pedidos</a>
      <?php endif; ?>
      <?php if (!$eExterno): ?>
        <div class="grupo">Central de Tarefas</div>
        <a href="/tarefas?view=quadro"<?= ($caminho === '/tarefas' && $visao === 'quadro' && empty($_GET['setor']) && empty($_GET['projeto'])) ? ' aria-current="page"' : '' ?>><span class="ico">▦</span> Quadro</a>
        <a href="/tarefas?view=tabela"<?= ($caminho === '/tarefas' && $visao === 'tabela' && empty($_GET['setor']) && empty($_GET['projeto'])) ? ' aria-current="page"' : '' ?>><span class="ico">☰</span> Tabela</a>
        <a href="/tarefas?view=calendario"<?= ($caminho === '/tarefas' && $visao === 'calendario' && empty($_GET['setor']) && empty($_GET['projeto'])) ? ' aria-current="page"' : '' ?>><span class="ico">▤</span> Calendário</a>
        <a href="/solicitacoes"<?= $ativo('/solicitacoes') ?>><span class="ico">✉</span> Solicitações MKT e Design</a>
        <div class="grupo">Marketing</div>
        <a href="/postagens"<?= $ativo('/postagens') ?>><span class="ico">▥</span> Cronograma de Postagens</a>
        <a href="/ideias"<?= $ativo('/ideias') ?>><span class="ico">✧</span> Ideias de Conteúdo</a>
        <a href="/blog"<?= $ativo('/blog') ?>><span class="ico">✎</span> Post - Blog</a>
        <div class="grupo">Equipe</div>
        <a href="/reunioes"<?= $ativo('/reunioes') ?>><span class="ico">☷</span> Reuniões</a>
        <a href="/quem-faz-o-que"<?= $ativo('/quem-faz-o-que') ?>><span class="ico">☺</span> Quem faz o quê</a>
        <a href="/missao"<?= $ativo('/missao') ?>><span class="ico">★</span> Missão, Visão e Valores</a>
        <div class="grupo">Setores</div>
        <?php foreach (Setores::arvore() as $s): ?>
          <a href="/setores/<?= (int) $s['id'] ?>"<?= ($caminho === '/setores/' . $s['id'] || (($_GET['setor'] ?? '') == $s['id'] && $caminho === '/tarefas')) ? ' aria-current="page"' : '' ?><?= $s['nivel'] ? ' class="sub"' : '' ?>><span class="pt" style="--c:<?= e($s['color']) ?>"></span> <?= e($s['name']) ?></a>
        <?php endforeach; ?>
        <div class="grupo">Projetos</div>
        <a href="/projetos"<?= $ativo('/projetos') ?>><span class="ico">◨</span> Visão geral</a>
        <?php foreach (Usuarios::projetos() as $s): ?>
          <a href="/tarefas?projeto=<?= (int) $s['id'] ?>"<?= (($_GET['projeto'] ?? '') == $s['id'] && $caminho === '/tarefas') ? ' aria-current="page"' : '' ?>><span class="pt" style="--c:<?= e($s['color']) ?>"></span> <?= e($s['name']) ?></a>
        <?php endforeach; ?>
      <?php endif; ?>
      <?php if ($eAdmin): ?>
        <div class="grupo">Administração</div>
        <a href="/admin/usuarios"<?= $ativo('/admin/usuarios') ?>><span class="ico">☺</span> Usuários</a>
        <a href="/admin/setores"<?= $ativo('/admin/setores') ?>><span class="ico">◧</span> Setores</a>
        <a href="/admin/projetos"<?= $ativo('/admin/projetos') ?>><span class="ico">◨</span> Projetos</a>
        <a href="/admin/quem-faz"<?= $ativo('/admin/quem-faz') ?>><span class="ico">☺</span> Quem faz o quê: editar</a>
        <a href="/admin/postagens"<?= $ativo('/admin/postagens') ?>><span class="ico">▥</span> Cronograma: listas</a>
        <a href="/admin/aparencia"<?= $ativo('/admin/aparencia') ?>><span class="ico">✦</span> Aparência</a>
        <a href="/admin/inicio"<?= $ativo('/admin/inicio') ?>><span class="ico">✎</span> Página inicial e textos</a>
        <a href="/admin/historico"<?= $ativo('/admin/historico') ?>><span class="ico">⟲</span> Histórico</a>
      <?php endif; ?>
    </nav>
  </aside>
  <label for="menu-toggle" class="fundo-menu" aria-hidden="true"></label>

  <div class="miolo">
    <header class="topo">
      <label for="menu-toggle" class="botao-menu" title="Menu">☰<span class="sr"> Menu</span></label>
      <div class="topo-esq"><h1 class="topo-titulo"><?= e($titulo) ?></h1></div>
      <div class="topo-dir">
        <?php if ($usuario && Perm::equipe($usuario)): ?>
          <a class="btn primario" href="/tarefas/nova">+ Nova tarefa</a>
        <?php endif; ?>
        <?php if ($usuario): ?>
        <details class="sino">
          <summary aria-label="Notificações<?= $naoLidas ? ': ' . $naoLidas . ' nova' . ($naoLidas > 1 ? 's' : '') : '' ?>"><span aria-hidden="true">🔔</span><?php if ($naoLidas): ?><b class="sino-n"><?= $naoLidas > 99 ? '99+' : $naoLidas ?></b><?php endif; ?></summary>
          <div class="menu-caixa sino-caixa">
            <?php foreach ($recentes as $n): ?>
              <a class="<?= $n['read_at'] ? 'lida' : 'nova' ?>" href="/notificacoes/<?= (int) $n['id'] ?>/abrir"><?= e($n['body']) ?><small><?= e(datahora_br($n['created_at'])) ?></small></a>
            <?php endforeach; ?>
            <?php if (!$recentes): ?><p class="suave sino-vazio">Nada por aqui ainda.</p><?php endif; ?>
            <a class="sino-todas" href="/notificacoes">Ver todas</a>
          </div>
        </details>
        <?php endif; ?>
        <details class="menu-usuario">
          <summary><?= avatar_html($usuario ?? ['name' => '?']) ?><span class="nome-usuario"><?= e($usuario['name'] ?? '') ?></span></summary>
          <div class="menu-caixa">
            <div class="menu-info"><strong><?= e($usuario['name'] ?? '') ?></strong><small><?= e($usuario['email'] ?? '') ?> · <?= e(Config::PAPEIS[$usuario['role'] ?? ''] ?? '') ?></small></div>
            <a href="/conta">Meu perfil (foto e tema)</a>
            <a href="/conta/senha">Trocar senha</a>
            <form method="post" action="/sair"><?= Csrf::campo() ?><button type="submit" class="link">Sair</button></form>
          </div>
        </details>
      </div>
    </header>

    <main id="principal" class="principal" tabindex="-1">
      <?php foreach (flash() ?? [] as $f): ?>
        <div class="aviso <?= e($f['tipo']) ?>" role="status"><?= e($f['msg']) ?></div>
      <?php endforeach; ?>
      <?= $conteudo ?>
    </main>
  </div>
</div>
<div id="toast" class="toast" role="status" aria-live="polite" hidden></div>
</body>
</html>
