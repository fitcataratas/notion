<?php
declare(strict_types=1);
/**
 * Aplica as atualizações do banco de dados (database/migrations/*.sql) que
 * ainda não rodaram. Roda sozinho ao iniciar o container e é seguro repetir.
 */
require __DIR__ . '/../app/src/Env.php';

$host = Env::get('DB_HOST', 'db');
$name = Env::get('DB_NAME', 'gestao');
$user = Env::get('DB_USER', 'gestao');
$pass = Env::get('DB_PASS', '');

try {
    $porta = Env::get('DB_PORT', '3306');
    $pdo = new PDO("mysql:host=$host;port=$porta;dbname=$name;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "[migrar] banco indisponível: " . $e->getMessage() . "\n");
    exit(1);
}

$pdo->exec("CREATE TABLE IF NOT EXISTS migracoes (
    nome VARCHAR(120) NOT NULL PRIMARY KEY,
    aplicada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$feitas = $pdo->query("SELECT nome FROM migracoes")->fetchAll(PDO::FETCH_COLUMN);
$arquivos = glob(__DIR__ . '/../database/migrations/*.sql');
sort($arquivos);

foreach ($arquivos as $arq) {
    $nome = basename($arq);
    if (in_array($nome, $feitas, true)) {
        continue;
    }
    echo "[migrar] aplicando $nome\n";
    // remove comentários de linha e separa os comandos
    $sql = preg_replace('/^\s*--.*$/m', '', file_get_contents($arq));
    foreach (preg_split('/;\s*\n/', $sql) as $cmd) {
        $cmd = trim($cmd);
        if ($cmd !== '') {
            $pdo->exec($cmd);
        }
    }
    $pdo->prepare("INSERT INTO migracoes (nome) VALUES (?)")->execute([$nome]);
}
echo "[migrar] banco de dados em dia.\n";
