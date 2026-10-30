#!/bin/sh
# Ao iniciar o container: espera o banco, aplica as atualizações do banco
# de dados (migrações) e só então sobe o site.
set -e

echo "[gestao] aguardando o banco de dados..."
i=0
until php /var/www/app/bin/migrar.php; do
  i=$((i+1))
  if [ "$i" -ge 30 ]; then
    echo "[gestao] não foi possível preparar o banco de dados."
    exit 1
  fi
  sleep 2
done

chown -R www-data:www-data /var/www/app/storage 2>/dev/null || true
exec "$@"
