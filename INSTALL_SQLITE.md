# Instalação do Módulo SQLite

## Problema
O módulo `php-sqlite3` não está instalado, causando erro 500 no histórico.

## Solução Rápida

Execute no terminal:

```bash
cd /var/www/html/consultanumero
sudo ./install_sqlite.sh
```

## Ou manualmente:

```bash
sudo apt-get update
sudo apt-get install -y php-sqlite3
sudo systemctl restart apache2
cd /var/www/html/consultanumero
sudo chmod 777 database
sudo chmod 666 database/consultas.db 2>/dev/null || true
sudo chown -R www-data:www-data database
```

## Verificar se funcionou

```bash
php -m | grep sqlite
```

Deve mostrar: `pdo_sqlite` e `sqlite3`

## Depois da instalação

1. Acesse: http://localhost/consultanumero/historico.php
2. Se necessário, execute: http://localhost/consultanumero/fix_permissions.php
3. Execute migração: http://localhost/consultanumero/migrate_web.php

