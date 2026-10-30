<?php /** @var array $usuarios */ ?>
<div class="cabeca-pagina">
  <p class="suave">Quem pode entrar no sistema e com qual perfil. Pessoas sem e-mail aparecem nas tarefas, mas ainda não conseguem entrar.</p>
  <a class="btn primario" href="/admin/usuarios/novo">+ Novo usuário</a>
</div>
<div class="tabela-rolagem">
<table class="tabela">
  <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Acesso</th><th>Último acesso</th></tr></thead>
  <tbody>
  <?php foreach ($usuarios as $x): ?>
    <tr class="<?= $x['active'] ? '' : 'inativo' ?>">
      <td class="col-titulo"><a href="/admin/usuarios/<?= (int) $x['id'] ?>"><?= e($x['name']) ?></a></td>
      <td><?= e($x['email'] ?: '—') ?></td>
      <td><?= e(Config::PAPEIS[$x['role']]) ?></td>
      <td>
        <?php if (!$x['active']): ?><span class="pr pr-baixa">Desativado</span>
        <?php elseif (!$x['tem_senha']): ?><span class="pr pr-media">Sem acesso ainda</span>
        <?php elseif ($x['must_change_password']): ?><span class="pr pr-media">Aguardando 1º acesso</span>
        <?php else: ?><span class="st st-concluido">Ativo</span><?php endif; ?>
      </td>
      <td class="nowrap"><?= e(datahora_br($x['last_login_at']) ?: '—') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
