#!/usr/bin/env bash
# Cópia de segurança do sistema: banco de dados + arquivos (pasta storage).
# Uso (na pasta do sistema):   ./bin/backup.sh [pasta-de-destino]
# Padrão: ./backups   — guarda as últimas 14 cópias.
# IMPORTANTE: depois, copie a pasta de backups para OUTRA máquina (ver LEIAME).
set -euo pipefail
cd "$(dirname "$0")/.."
DEST="${1:-./backups}"
MANTER="${MANTER:-14}"
mkdir -p "$DEST"
DATA="$(date +%Y-%m-%d_%H%M)"
set -a; . ./.env; set +a

echo "Salvando banco de dados..."
docker compose exec -T db sh -c 'exec mariadb-dump --single-transaction --routines -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' \
  | gzip > "$DEST/banco_$DATA.sql.gz"

echo "Salvando arquivos..."
tar -czf "$DEST/arquivos_$DATA.tar.gz" -C . storage --exclude='storage/sessions' --exclude='storage/logs'

# apaga as mais antigas
ls -1t "$DEST"/banco_*.sql.gz 2>/dev/null | tail -n +$((MANTER+1)) | xargs -r rm -f
ls -1t "$DEST"/arquivos_*.tar.gz 2>/dev/null | tail -n +$((MANTER+1)) | xargs -r rm -f
echo "Pronto: $DEST/banco_$DATA.sql.gz e $DEST/arquivos_$DATA.tar.gz"
