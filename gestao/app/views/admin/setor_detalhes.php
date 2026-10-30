<?php /** @var array $s @var array $erros @var array $pessoas @var array $outros */
$atuais = [];
foreach ($s['responsaveis'] as $r) { $atuais[(int) $r['id']] = $r['role_label']; }
// quem já é responsável aparece primeiro
usort($pessoas, fn($a, $b) => [isset($atuais[(int) $b['id']]), $a['name']] <=> [isset($atuais[(int) $a['id']]), $b['name']]);
?>
<div class="cabeca-pagina"><a class="voltar" href="/setores/<?= (int) $s['id'] ?>">← Página do setor</a></div>
<?php foreach ($erros as $er): ?><div class="aviso erro" role="alert"><?= e($er) ?></div><?php endforeach; ?>

<form method="post" action="/admin/setores/<?= (int) $s['id'] ?>/detalhes" class="formulario cartao estreito">
  <?= Csrf::campo() ?>
  <h2><?= e($s['name']) ?></h2>
  <p class="suave">O nome e a cor do setor se mudam em <a href="/admin/setores">Administração → Setores</a>.</p>

  <label>Descrição do setor <small>(aparece no alto da página do setor)</small>
    <textarea name="description" rows="4" maxlength="3000"><?= e($s['description'] ?? '') ?></textarea>
  </label>

  <label>Este setor faz parte de outro?
    <select name="parent_id">
      <option value="">Não, é um setor principal</option>
      <?php foreach ($outros as $o): ?><option value="<?= (int) $o['id'] ?>"<?= (int) ($s['parent_id'] ?? 0) === (int) $o['id'] ? ' selected' : '' ?>><?= e($o['name']) ?></option><?php endforeach; ?>
    </select>
    <small>Exemplo: Design dentro de Marketing. No menu, aparece logo abaixo do setor principal.</small>
  </label>

  <fieldset>
    <legend>Responsáveis</legend>
    <p class="suave">Marque quem é responsável e escreva a função (ex.: Responsável, Cocoordenadora). Os contatos mostrados são os e-mails cadastrados em Usuários.</p>
    <div class="lista-responsaveis">
      <?php foreach ($pessoas as $p): $uid = (int) $p['id']; $marcada = isset($atuais[$uid]); ?>
        <div class="linha-resp">
          <label class="marca-simples"><input type="checkbox" name="resp[]" value="<?= $uid ?>"<?= $marcada ? ' checked' : '' ?>> <?= e($p['name']) ?></label>
          <input type="text" name="funcao[<?= $uid ?>]" value="<?= e($atuais[$uid] ?? '') ?>" placeholder="Responsável" maxlength="60" aria-label="Função de <?= e($p['name']) ?>">
        </div>
      <?php endforeach; ?>
    </div>
  </fieldset>
  <div class="acoes"><button class="btn primario" type="submit">Salvar</button><a class="btn texto" href="/setores/<?= (int) $s['id'] ?>">Cancelar</a></div>
</form>
