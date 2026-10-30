<?php /** @var array $linhas @var string $ent */ ?>
<form method="get" action="/admin/historico" class="filtros">
  <select name="entidade" aria-label="Tipo de registro">
    <option value="">Tudo</option>
    <?php foreach (['tarefa' => 'Tarefas', 'usuario' => 'Usuários e acessos', 'setor' => 'Setores', 'projeto' => 'Projetos', 'configuracao' => 'Configurações', 'pagina' => 'Páginas e links dos setores', 'postagem' => 'Cronograma de postagens', 'blog' => 'Blog (ideias e pautas)', 'reuniao' => 'Reuniões'] as $k => $r): ?>
      <option value="<?= e($k) ?>"<?= $ent === $k ? ' selected' : '' ?>><?= e($r) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn" type="submit">Filtrar</button>
</form>
<p class="suave">Os 300 registros mais recentes: quem fez o quê, quando e de qual endereço de rede.</p>
<div class="tabela-rolagem">
<table class="tabela">
  <thead><tr><th>Quando</th><th>Quem</th><th>O que aconteceu</th><th>Rede</th></tr></thead>
  <tbody>
  <?php foreach ($linhas as $l): ?>
    <tr>
      <td class="nowrap"><?= e(datahora_br($l['created_at'])) ?></td>
      <td><?= e($l['user_name'] ?? '—') ?></td>
      <td>
        <?php if ($l['entity'] === 'tarefa' && $l['entity_id'] && $l['action'] !== 'excluir'): ?>
          <a href="/tarefas/<?= (int) $l['entity_id'] ?>"><?= e($l['summary']) ?></a>
        <?php else: ?><?= e($l['summary']) ?><?php endif; ?>
      </td>
      <td class="nowrap"><?= e($l['ip']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
