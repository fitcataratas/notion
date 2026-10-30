# Subir o sistema no servidor (guia para o João Pedro — TI)

Pré-requisitos: Ubuntu 22.04+ com Docker + plugin compose, Nginx, certbot e um domínio apontado para o servidor
(ex.: `gestao.seudominio.com.br`). A pasta `gestao` deste pacote é o sistema completo.

## 1. Instalar
```bash
sudo mkdir -p /opt/gestao && sudo chown $USER /opt/gestao
# copie o conteúdo da pasta "gestao" do zip para /opt/gestao
cd /opt/gestao
cp .env.example .env
nano .env        # APP_URL=https://gestao.seudominio.com.br  APP_ENV=producao
                 # DB_PASS e DB_ROOT_PASS: gere com  openssl rand -base64 24
chmod 600 .env
docker compose up -d --build
docker compose logs -f app     # esperar "[migrar] banco de dados em dia."
```
O banco é criado e atualizado sozinho. A porta 8080 só escuta em 127.0.0.1.

## 2. Nginx + HTTPS
```bash
sudo cp ferramentas/nginx-gestao.conf /etc/nginx/sites-available/gestao
sudo nano /etc/nginx/sites-available/gestao        # trocar o domínio
sudo ln -s /etc/nginx/sites-available/gestao /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d gestao.seudominio.com.br
```
Com `https://` no `APP_URL` o login só funciona por conexão segura (é o esperado).

## 3. Primeiro acesso e dados
**Opção A — começar do zero no servidor** (comandos rodados uma única vez; não repetir):
```bash
docker compose exec app php bin/criar-admin.php
docker compose exec app php bin/importar-notion.php
docker compose exec app php bin/carregar-responsaveis.php
docker compose exec app php bin/importar-postagens.php
docker compose exec app php bin/importar-blog.php
docker compose exec app php bin/importar-reunioes.php
```
**Opção B — levar o que já está no computador da Mayara** (mantém usuários, senhas, fotos e tudo o que já foi
editado; é a melhor opção na virada). No computador dela (PowerShell, pasta `gestao`):
```powershell
docker compose exec -T db sh -c 'exec mariadb-dump --single-transaction --routines -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' > banco.sql
Compress-Archive storage\fotos,storage\aparencia -DestinationPath arquivos.zip
```
No servidor, com o sistema já no ar (passo 1):
```bash
docker compose exec -T db sh -c 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' < banco.sql
unzip arquivos.zip -d storage/ && docker compose restart app
```
Use o **mesmo `.env` de senhas novas no servidor**; as senhas dos usuários do sistema vêm dentro do banco.

## 4. Segurança
```bash
sudo ufw allow OpenSSH && sudo ufw allow 80,443/tcp && sudo ufw enable
sudo apt install unattended-upgrades
```
SSH por chave, sem senha. O banco não tem porta publicada (só o container do site o alcança).

## 5. Backup automático (obrigatório)
```bash
crontab -e
0 3 * * * cd /opt/gestao && ./bin/backup.sh >> /var/log/gestao-backup.log 2>&1
```
Guarda as últimas 14 cópias em `/opt/gestao/backups`. **Copie essa pasta para outra máquina/nuvem** (rsync, rclone
para o Drive etc.) — cópia só no mesmo servidor não protege se ele falhar.
**Teste a restauração uma vez** antes de a equipe usar: `./bin/restaurar.sh backups/banco_AAAA-MM-DD_HHMM.sql.gz`.

## 6. Atualizar para uma versão nova
```bash
cd /opt/gestao
./bin/backup.sh
# extrair o zip novo por cima (o .env e a pasta storage ficam como estão)
docker compose up -d --build
```
Só rodar os comandos `importar-…` que o LEIAME da versão pedir (nunca `criar-admin`/`importar-notion` de novo).

## 7. Conferência final
- [ ] `https://…` abre com cadeado e o login funciona
- [ ] logo e fotos aparecem; criar/editar uma tarefa funciona
- [ ] `docker compose ps` mostra `app` e `db` ativos; reiniciar o servidor sobe tudo sozinho (`restart: unless-stopped`)
- [ ] backup rodou uma vez e foi testado; cópia fora do servidor configurada
- [ ] IP real nos logs de acesso (`docker compose logs app`), não só 127.0.0.1
- [ ] cada pessoa da equipe fez login e trocou a senha provisória
