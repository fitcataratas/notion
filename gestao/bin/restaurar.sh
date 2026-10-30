#!/usr/bin/env bash
# Restaura uma cópia de segurança.  ATENÇÃO: substitui os dados atuais.
# Uso:  ./bin/restaurar.sh backups/banco_2026-10-29_0300.sql.gz [backups/arquivos_2026-10-29_0300.tar.gz]
set -euo pipefail
cd "$(dirname "$0")/.."
[ -f "${1:-}" ] || { echo "Informe o arquivo banco_*.sql.gz"; exit 1; }
read -r -p "Isto APAGA os dados atuais e volta para a cópia escolhida. Digite SIM para continuar: " ok
[ "$ok" = "SIM" ] || { echo "Cancelado."; exit 1; }
set -a; . ./.env; set +a
docker compose exec -T db sh -c 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS \`$MARIADB_DATABASE\`; CREATE DATABASE \`$MARIADB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"'
gunzip -c "$1" | docker compose exec -T db sh -c 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"'
[ -f "${2:-}" ] && tar -xzf "$2" -C .
echo "Restaurado."
