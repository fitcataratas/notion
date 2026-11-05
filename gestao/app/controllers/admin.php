<?php
declare(strict_types=1);

// Telas de estrutura: só administradores (memorial, seção 27).

// ------------------------------------------------------------------ usuários
Router::add('GET', '/admin/usuarios', function (array $p, array $u) {
    View::render('admin/usuarios', ['titulo' => 'Usuários', 'usuarios' => Usuarios::todos()]);
}, ['papel' => 'admin']);

Router::add('GET', '/admin/usuarios/novo', function (array $p, array $u) {
    View::render('admin/usuario_form', ['titulo' => 'Novo usuário', 'x' => ['role' => 'equipe', 'active' => 1], 'id' => null, 'erros' => []]);
}, ['papel' => 'admin']);

Router::add('POST', '/admin/usuarios', function (array $p, array $u) {
    $erros = Usuarios::validar($_POST);
    $nome = trim((string) ($_POST['name'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? ''))) ?: null;
    if ($erros) {
        http_response_code(422);
        View::render('admin/usuario_form', ['titulo' => 'Novo usuário', 'x' => ['name' => $nome, 'email' => $email, 'role' => $_POST['role'] ?? 'equipe', 'active' => 1], 'id' => null, 'erros' => $erros]);
        return;
    }
    $senha = $email ? gerar_senha() : null;
    Db::run('INSERT INTO users (name, email, password_hash, role, must_change_password) VALUES (?,?,?,?,?)', [
        $nome, $email, $senha ? password_hash($senha, PASSWORD_DEFAULT) : null, $_POST['role'], $senha ? 1 : 0,
    ]);
    $id = Db::insertId();
    Audit::registrar('criar', 'usuario', $id, "Criou o usuário $nome (" . Config::PAPEIS[$_POST['role']] . ')', null, $u);
    if ($senha) {
        $_SESSION['senha_temp'] = ['id' => $id, 'nome' => $nome, 'senha' => $senha];
    }
    flash('Usuário criado.');
    redirect('/admin/usuarios/' . $id);
}, ['papel' => 'admin']);

Router::add('GET', '/admin/usuarios/(?P<id>\d+)', function (array $p, array $u) {
    $x = Usuarios::obter((int) $p['id']);
    if (!$x) {
        View::naoEncontrado();
    }
    $temp = $_SESSION['senha_temp'] ?? null;
    unset($_SESSION['senha_temp']);   // a senha provisória só aparece uma vez
    if ($temp && $temp['id'] !== (int) $x['id']) {
        $temp = null;
    }
    View::render('admin/usuario_form', ['titulo' => $x['name'], 'x' => $x, 'id' => (int) $x['id'], 'erros' => [], 'temp' => $temp, 'eu' => $u]);
}, ['papel' => 'admin']);

Router::add('POST', '/admin/usuarios/(?P<id>\d+)', function (array $p, array $u) {
    $id = (int) $p['id'];
    $x = Usuarios::obter($id);
    if (!$x) {
        View::naoEncontrado();
    }
    $erros = Usuarios::validar($_POST, $id);
    $ativo = !empty($_POST['active']) ? 1 : 0;
    $papel = (string) ($_POST['role'] ?? '');
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? ''))) ?: null;

    if ($id === (int) $u['id'] && ($ativo === 0 || $papel !== 'admin')) {
        $erros[] = 'Você não pode desativar nem rebaixar o seu próprio acesso. Peça para outro administrador.';
    }
    if ($x['role'] === 'admin' && ($ativo === 0 || $papel !== 'admin') && Usuarios::totalAdminsAtivos() <= 1 && $x['tem_senha']) {
        $erros[] = 'O sistema precisa manter pelo menos um administrador ativo.';
    }
    if ($erros) {
        http_response_code(422);
        View::render('admin/usuario_form', ['titulo' => $x['name'], 'x' => array_merge($x, ['name' => $_POST['name'] ?? $x['name'], 'email' => $email, 'role' => $papel, 'active' => $ativo]), 'id' => $id, 'erros' => $erros, 'eu' => $u]);
        return;
    }
    Db::run('UPDATE users SET name = ?, email = ?, role = ?, active = ? WHERE id = ?', [trim((string) $_POST['name']), $email, $papel, $ativo, $id]);
    $mud = [];
    if ($x['role'] !== $papel) {
        $mud[] = 'perfil ' . Config::PAPEIS[$x['role']] . ' → ' . Config::PAPEIS[$papel];
    }
    if ((int) $x['active'] !== $ativo) {
        $mud[] = $ativo ? 'reativado' : 'desativado';
    }
    if ($x['email'] !== $email) {
        $mud[] = 'e-mail alterado';
    }
    Audit::registrar('editar', 'usuario', $id, 'Alterou o usuário ' . trim((string) $_POST['name']) . ($mud ? ': ' . implode('; ', $mud) : ''), null, $u);
    flash('Usuário atualizado.');
    redirect('/admin/usuarios/' . $id);
}, ['papel' => 'admin']);

Router::add('POST', '/admin/usuarios/(?P<id>\d+)/senha', function (array $p, array $u) {
    $id = (int) $p['id'];
    $x = Usuarios::obter($id);
    if (!$x) {
        View::naoEncontrado();
    }
    if (empty($x['email'])) {
        flash('Cadastre o e-mail da pessoa antes de gerar uma senha.', 'erro');
        redirect('/admin/usuarios/' . $id);
    }
    $senha = gerar_senha();
    Db::run('UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?', [password_hash($senha, PASSWORD_DEFAULT), $id]);
    // zera bloqueios de tentativas para a pessoa conseguir entrar
    Db::run('DELETE FROM login_attempts WHERE email = ?', [mb_strtolower((string) $x['email'])]);
    Audit::registrar('senha_provisoria', 'usuario', $id, 'Gerou senha provisória para ' . $x['name'], null, $u);
    $_SESSION['senha_temp'] = ['id' => $id, 'nome' => $x['name'], 'senha' => $senha];
    redirect('/admin/usuarios/' . $id);
}, ['papel' => 'admin']);

Router::add('POST', '/admin/usuarios/(?P<id>\d+)/foto', function (array $p, array $u) {
    $id = (int) $p['id'];
    if (!Usuarios::obter($id)) {
        View::naoEncontrado();
    }
    $erro = Usuarios::definirFoto($id, $_FILES['foto'] ?? null);
    $erro ? flash($erro, 'erro') : flash('Foto atualizada.');
    redirect('/admin/usuarios/' . $id);
}, ['papel' => 'admin']);

Router::add('POST', '/admin/usuarios/(?P<id>\d+)/foto/remover', function (array $p, array $u) {
    $id = (int) $p['id'];
    if (!Usuarios::obter($id)) {
        View::naoEncontrado();
    }
    Usuarios::removerFoto($id);
    flash('Foto removida.');
    redirect('/admin/usuarios/' . $id);
}, ['papel' => 'admin']);

// Apagar de vez. Para quem saiu da empresa, o mais seguro costuma ser desativar (a pessoa continua
// aparecendo nas tarefas antigas); apagar tira o nome dela das tarefas e deixa os comentários sem autor.
Router::add('POST', '/admin/usuarios/(?P<id>\d+)/excluir', function (array $p, array $u) {
    $id = (int) $p['id'];
    $x = Usuarios::obter($id);
    if (!$x) {
        View::naoEncontrado();
    }
    if ($id === (int) $u['id']) {
        flash('Você não pode apagar o seu próprio usuário.', 'erro');
        redirect('/admin/usuarios/' . $id);
    }
    if ($x['role'] === 'admin' && (int) $x['active'] === 1 && $x['tem_senha'] && Usuarios::totalAdminsAtivos() <= 1) {
        flash('O sistema precisa manter pelo menos um administrador ativo.', 'erro');
        redirect('/admin/usuarios/' . $id);
    }
    $usos = Usuarios::usos($id);
    Usuarios::removerFoto($id);
    Db::run('DELETE FROM users WHERE id = ?', [$id]);
    Audit::registrar('excluir', 'usuario', $id, "Apagou o usuário {$x['name']} (estava em {$usos['tarefas']} tarefa(s), {$usos['comentarios']} comentário(s))", null, $u);
    flash("Usuário {$x['name']} apagado.");
    redirect('/admin/usuarios');
}, ['papel' => 'admin']);

// ----------------------------------------------------------------- aparência
Router::add('GET', '/admin/aparencia', function (array $p, array $u) {
    View::render('admin/aparencia', ['titulo' => 'Aparência', 'a' => Aparencia::todas(), 'erros' => []]);
}, ['papel' => 'admin']);

Router::add('POST', '/admin/aparencia', function (array $p, array $u) {
    $erros = Aparencia::salvar($_POST);
    if ($erros) {
        http_response_code(422);
        View::render('admin/aparencia', ['titulo' => 'Aparência', 'a' => array_merge(Aparencia::todas(), $_POST), 'erros' => $erros]);
        return;
    }
    Audit::registrar('editar', 'configuracao', null, 'Alterou a aparência do sistema', null, $u);
    flash('Aparência salva.');
    redirect('/admin/aparencia');
}, ['papel' => 'admin']);

Router::add('POST', '/admin/aparencia/logo', function (array $p, array $u) {
    $r = Imagem::processar($_FILES['logo'] ?? null, 'logo');
    if (!$r['ok']) {
        flash($r['erro'], 'erro');
    } else {
        $antigo = Aparencia::get('logo');
        Aparencia::gravar('logo', Imagem::guardar('aparencia', $r['bin'], 'png'));
        Imagem::apagar('aparencia', $antigo, 'png');
        Audit::registrar('editar', 'configuracao', null, 'Trocou o logo do sistema', null, $u);
        flash('Logo atualizado.');
    }
    redirect('/admin/aparencia');
}, ['papel' => 'admin']);

Router::add('POST', '/admin/aparencia/logo/remover', function (array $p, array $u) {
    Imagem::apagar('aparencia', Aparencia::get('logo'), 'png');
    Aparencia::gravar('logo', '');
    flash('Logo removido.');
    redirect('/admin/aparencia');
}, ['papel' => 'admin']);

Router::add('POST', '/admin/aparencia/restaurar', function (array $p, array $u) {
    Aparencia::restaurar();
    Audit::registrar('editar', 'configuracao', null, 'Voltou a aparência para o padrão', null, $u);
    flash('Aparência restaurada para o padrão.');
    redirect('/admin/aparencia');
}, ['papel' => 'admin']);

// ------------------------------------------------- setores e projetos (mesma tela)
foreach ([
    'setores' => ['tabela' => 'sectors', 'tarefas' => 'task_sectors', 'coluna' => 'sector_id', 'rotulo' => 'Setores', 'um' => 'setor'],
    'projetos' => ['tabela' => 'projects', 'tarefas' => 'task_projects', 'coluna' => 'project_id', 'rotulo' => 'Projetos', 'um' => 'projeto'],
] as $rota => $cfg) {
    Router::add('GET', "/admin/$rota", function (array $p, array $u) use ($rota, $cfg) {
        $itens = $rota === 'setores' ? Usuarios::setores() : Usuarios::projetos();
        View::render('admin/catalogo', ['titulo' => $cfg['rotulo'], 'rota' => $rota, 'cfg' => $cfg, 'itens' => $itens]);
    }, ['papel' => 'admin']);

    Router::add('POST', "/admin/$rota", function (array $p, array $u) use ($rota, $cfg) {
        $nome = trim((string) ($_POST['name'] ?? ''));
        if ($nome === '' || mb_strlen($nome) > 80) {
            flash('Informe um nome (até 80 letras).', 'erro');
        } elseif (Db::value("SELECT 1 FROM {$cfg['tabela']} WHERE name = ?", [$nome])) {
            flash('Já existe um item com esse nome.', 'erro');
        } else {
            $ordem = (int) Db::value("SELECT COALESCE(MAX(sort_order),0)+1 FROM {$cfg['tabela']}");
            Db::run("INSERT INTO {$cfg['tabela']} (name, color, sort_order) VALUES (?,?,?)", [$nome, cor_valida($_POST['color'] ?? null), $ordem]);
            Audit::registrar('criar', $cfg['um'], Db::insertId(), "Criou o {$cfg['um']} $nome", null, $u);
            flash(ucfirst($cfg['um']) . ' criado.');
        }
        redirect("/admin/$rota");
    }, ['papel' => 'admin']);

    Router::add('POST', "/admin/$rota/(?P<id>\\d+)", function (array $p, array $u) use ($rota, $cfg) {
        $id = (int) $p['id'];
        $nome = trim((string) ($_POST['name'] ?? ''));
        if (!Db::value("SELECT 1 FROM {$cfg['tabela']} WHERE id = ?", [$id])) {
            View::naoEncontrado();
        }
        if ($nome === '' || mb_strlen($nome) > 80) {
            flash('Informe um nome (até 80 letras).', 'erro');
        } elseif (Db::value("SELECT 1 FROM {$cfg['tabela']} WHERE name = ? AND id <> ?", [$nome, $id])) {
            flash('Já existe um item com esse nome.', 'erro');
        } else {
            Db::run("UPDATE {$cfg['tabela']} SET name = ?, color = ? WHERE id = ?", [$nome, cor_valida($_POST['color'] ?? null), $id]);
            Audit::registrar('editar', $cfg['um'], $id, "Alterou o {$cfg['um']} $nome", null, $u);
            flash('Alterações salvas.');
        }
        redirect("/admin/$rota");
    }, ['papel' => 'admin']);

    Router::add('POST', "/admin/$rota/(?P<id>\\d+)/excluir", function (array $p, array $u) use ($rota, $cfg) {
        $id = (int) $p['id'];
        $usos = (int) Db::value("SELECT COUNT(*) FROM {$cfg['tarefas']} WHERE {$cfg['coluna']} = ?", [$id]);
        $nome = (string) Db::value("SELECT name FROM {$cfg['tabela']} WHERE id = ?", [$id]);
        if ($usos > 0) {
            flash("Não dá para excluir \"$nome\": $usos tarefa(s) ainda usam. Troque o {$cfg['um']} nelas primeiro.", 'erro');
        } elseif ($nome !== '') {
            Db::run("DELETE FROM {$cfg['tabela']} WHERE id = ?", [$id]);
            Audit::registrar('excluir', $cfg['um'], $id, "Excluiu o {$cfg['um']} $nome", null, $u);
            flash(ucfirst($cfg['um']) . ' excluído.');
        }
        redirect("/admin/$rota");
    }, ['papel' => 'admin']);
}

// ---------------------------------------------------- texto da página inicial
Router::add('GET', '/admin/inicio', function (array $p, array $u) {
    $t = [];
    foreach (Institucional::CHAVES as $k) {
        $t[$k] = Institucional::texto($k);
    }
    View::render('admin/inicio', ['titulo' => 'Página inicial e textos', 't' => $t]);
}, ['papel' => 'admin']);

Router::add('POST', '/admin/inicio', function (array $p, array $u) {
    $in = [];
    foreach (Institucional::CHAVES as $k) {
        if (isset($_POST[$k])) {
            $in[$k] = $_POST[$k];
        }
    }
    if (isset($_POST['datas'])) {
        $in['datas_importantes'] = $_POST['datas'];
    }
    Institucional::salvarTextos($in, $u);
    flash('Textos atualizados.');
    redirect('/admin/inicio');
}, ['papel' => 'admin']);

Router::add('POST', '/admin/avisos', function (array $p, array $u) {
    $pagina = (string) ($_POST['pagina'] ?? '');
    $ok = $pagina !== '' && Avisos::chave($pagina === 'inicio' ? '/' : (str_starts_with($pagina, 'setor-') ? '/setores/' . substr($pagina, 6) : '/' . $pagina)) === $pagina && Avisos::criar($pagina, $_POST, $u);
    $ok ? flash('Aviso adicionado.') : flash('Escreva o texto do aviso.', 'erro');
    redirect(volta_segura($_POST['volta'] ?? '/'));
}, ['papel' => 'admin']);

Router::add('POST', '/admin/avisos/(?P<id>\d+)', function (array $p, array $u) {
    Avisos::salvar((int) $p['id'], $_POST, $u) ? flash('Aviso salvo.') : View::naoEncontrado();
    redirect(volta_segura($_POST['volta'] ?? '/'));
}, ['papel' => 'admin']);

Router::add('POST', '/admin/avisos/(?P<id>\d+)/excluir', function (array $p, array $u) {
    Avisos::excluir((int) $p['id'], $u) ? flash('Aviso apagado.') : View::naoEncontrado();
    redirect(volta_segura($_POST['volta'] ?? '/'));
}, ['papel' => 'admin']);

Router::add('POST', '/admin/blog-aviso', function (array $p, array $u) {
    Institucional::salvarAvisoBlog($_POST, $u);
    flash('Aviso do Blog atualizado.');
    redirect('/blog');
}, ['papel' => 'admin']);

// ------------------------------------------------------------------ histórico
Router::add('GET', '/admin/historico', function (array $p, array $u) {
    $ent = in_array($_GET['entidade'] ?? '', ['tarefa', 'usuario', 'setor', 'projeto', 'configuracao', 'pagina', 'postagem', 'blog', 'reuniao', 'editorial'], true) ? $_GET['entidade'] : '';
    $linhas = Db::all(
        'SELECT * FROM audit_log ' . ($ent ? 'WHERE entity = ? ' : '') . 'ORDER BY id DESC LIMIT 300',
        $ent ? [$ent] : []
    );
    View::render('admin/historico', ['titulo' => 'Histórico', 'linhas' => $linhas, 'ent' => $ent]);
}, ['papel' => 'admin']);
