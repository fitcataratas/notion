<?php
declare(strict_types=1);
/**
 * Cria (ou promove) um administrador. Use no primeiro acesso:
 *
 *   docker compose exec app php bin/criar-admin.php
 *
 * O programa pergunta nome, e-mail e senha. A senha não aparece na tela.
 * Também aceita:  php bin/criar-admin.php "Nome" "email@dominio.com"  (pergunta só a senha)
 */
require __DIR__ . '/../app/src/Env.php';
require __DIR__ . '/../app/src/Db.php';
require __DIR__ . '/../app/src/Config.php';
require __DIR__ . '/../app/src/helpers.php';
require __DIR__ . '/../app/src/Auth.php';
require __DIR__ . '/../app/src/Audit.php';

function perguntar(string $texto, bool $oculto = false): string
{
    echo $texto;
    if ($oculto && DIRECTORY_SEPARATOR === '/') {
        system('stty -echo 2>/dev/null');
    }
    $r = trim((string) fgets(STDIN));
    if ($oculto && DIRECTORY_SEPARATOR === '/') {
        system('stty echo 2>/dev/null');
        echo "\n";
    }
    return $r;
}

$nome = $argv[1] ?? perguntar('Nome completo: ');
$email = mb_strtolower($argv[2] ?? perguntar('E-mail (será o login): '));
if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Nome ou e-mail inválido.\n");
    exit(1);
}
$senha = getenv('ADMIN_SENHA') ?: perguntar('Senha (mín. ' . Config::SENHA_MIN . ' caracteres, com letras e números): ', true);
if ($msg = Auth::validarSenha($senha, $email)) {
    fwrite(STDERR, "$msg\n");
    exit(1);
}
if (!getenv('ADMIN_SENHA') && $senha !== perguntar('Repita a senha: ', true)) {
    fwrite(STDERR, "As senhas não são iguais.\n");
    exit(1);
}

$hash = password_hash($senha, PASSWORD_DEFAULT);
$existente = Db::one('SELECT id FROM users WHERE email = ? OR name = ?', [$email, $nome]);
if ($existente) {
    Db::run("UPDATE users SET name = ?, email = ?, password_hash = ?, role = 'admin', active = 1, must_change_password = 0 WHERE id = ?", [$nome, $email, $hash, $existente['id']]);
    echo "Usuário existente promovido a administrador: $nome <$email>\n";
} else {
    Db::run("INSERT INTO users (name, email, password_hash, role, active) VALUES (?,?,?, 'admin', 1)", [$nome, $email, $hash]);
    echo "Administrador criado: $nome <$email>\n";
}
Audit::registrar('criar_admin', 'usuario', null, "Administrador criado/promovido pela linha de comando: $nome", null, ['id' => null, 'name' => 'linha de comando']);
