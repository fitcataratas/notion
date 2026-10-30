<?php
/** @var array $x @var int|null $id @var array $erros @var array|null $temp @var array|null $eu */
$temp ??= null;
$eu ??= null;
$souEu = $id && $eu && (int) $eu['id'] === $id;
?>
<div class="cabeca-pagina"><a class="voltar" href="/admin/usuarios">← Usuários</a></div>

<?php foreach ($erros as $er): ?><div class="aviso erro" role="alert"><?= e($er) ?></div><?php endforeach; ?>

<?php if ($temp): ?>
  <section class="cartao senha-temp" role="alert">
    <h3>Senha provisória de <?= e($temp['nome']) ?></h3>
    <p>Anote e entregue à pessoa agora. <strong>Esta senha não será mostrada de novo.</strong> No primeiro acesso, o sistema pede que ela crie uma senha própria.</p>
    <p><code class="senha" id="senha-gerada"><?= e($temp['senha']) ?></code> <button type="button" class="btn" data-copiar="#senha-gerada">Copiar</button></p>
  </section>
<?php endif; ?>

<section class="cartao estreito">
  <form method="post" action="<?= $id ? '/admin/usuarios/' . (int) $id : '/admin/usuarios' ?>" class="formulario">
    <?= Csrf::campo() ?>
    <label>Nome<input type="text" name="name" value="<?= e($x['name'] ?? '') ?>" required maxlength="120"></label>
    <label>E-mail <small>(é o login da pessoa)</small><input type="email" name="email" value="<?= e($x['email'] ?? '') ?>" maxlength="190" autocomplete="off"></label>
    <label>Perfil de acesso
      <select name="role">
        <?php foreach (Config::PAPEIS as $k => $rot): ?><option value="<?= e($k) ?>"<?= ($x['role'] ?? 'equipe') === $k ? ' selected' : '' ?>><?= e($rot) ?></option><?php endforeach; ?>
      </select>
      <small><strong>Administrador:</strong> tudo, inclusive usuários, setores e excluir. <strong>Equipe interna:</strong> cria e edita tarefas. <strong>Externo:</strong> só vê as tarefas em que é responsável.</small>
    </label>
    <?php if ($id): ?>
      <label class="marca-simples"><input type="checkbox" name="active" value="1"<?= !empty($x['active']) ? ' checked' : '' ?><?= $souEu ? ' disabled' : '' ?>> Acesso ativo</label>
      <?php if ($souEu): ?><input type="hidden" name="active" value="1"><?php endif; ?>
    <?php else: ?>
      <p class="suave">Se você informar o e-mail, o sistema cria uma senha provisória e mostra na próxima tela.</p>
    <?php endif; ?>
    <div class="acoes"><button class="btn primario" type="submit"><?= $id ? 'Salvar' : 'Criar usuário' ?></button></div>
  </form>
</section>

<?php if ($id): ?>
<section class="cartao estreito">
  <h3>Senha</h3>
  <p class="suave">Esqueceu a senha? Gere uma provisória. A senha antiga deixa de valer e a pessoa cria uma nova no próximo acesso.</p>
  <form method="post" action="/admin/usuarios/<?= (int) $id ?>/senha" data-confirmar="Gerar uma nova senha provisória para <?= e($x['name']) ?>? A senha atual deixa de funcionar.">
    <?= Csrf::campo() ?>
    <button class="btn" type="submit"<?= empty($x['email']) ? ' disabled title="Cadastre o e-mail primeiro"' : '' ?>><?= !empty($x['tem_senha']) ? 'Gerar nova senha provisória' : 'Liberar acesso (gerar senha)' ?></button>
  </form>
</section>

<section class="cartao estreito">
  <h3>Foto</h3>
  <div class="linha-foto">
    <?= avatar_html($x, 'grande') ?>
    <div>
      <form method="post" action="/admin/usuarios/<?= (int) $id ?>/foto" enctype="multipart/form-data" class="formulario">
        <?= Csrf::campo() ?>
        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" required aria-label="Escolher foto">
        <small>JPG, PNG ou WEBP, até 5 MB. O sistema recorta em quadrado e reduz sozinho.</small>
        <div class="acoes"><button class="btn" type="submit">Enviar foto</button></div>
      </form>
      <?php if (!empty($x['photo'])): ?>
        <form method="post" action="/admin/usuarios/<?= (int) $id ?>/foto/remover" data-confirmar="Remover a foto de <?= e($x['name']) ?>?">
          <?= Csrf::campo() ?><button class="btn" type="submit">Remover foto</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if (!$souEu): ?>
<?php $usos = Usuarios::usos((int) $id); ?>
<section class="cartao estreito zona-perigo">
  <h3>Apagar usuário</h3>
  <p class="suave">
    <?php if ($usos['tarefas'] || $usos['comentarios']): ?>
      <?= e($x['name']) ?> aparece em <strong><?= $usos['tarefas'] ?> tarefa(s)</strong> e fez <strong><?= $usos['comentarios'] ?> comentário(s)</strong>.
      Se a pessoa saiu da empresa, o mais seguro é <strong>desmarcar "Acesso ativo"</strong> acima: ela não entra mais e o nome continua nas tarefas antigas.
      Apagando, o nome some dessas tarefas e os comentários ficam como "Usuário removido". As tarefas em si não são apagadas.
    <?php else: ?>
      Essa pessoa não está em nenhuma tarefa nem fez comentários, então pode ser apagada sem perder nada.
    <?php endif; ?>
    <strong>Não dá para desfazer.</strong>
  </p>
  <form method="post" action="/admin/usuarios/<?= (int) $id ?>/excluir" data-confirmar="Apagar o usuário <?= e($x['name']) ?> de vez? Isto não pode ser desfeito.">
    <?= Csrf::campo() ?>
    <button class="btn perigo" type="submit">Apagar <?= e($x['name']) ?></button>
  </form>
</section>
<?php endif; ?>
<?php endif; ?>
