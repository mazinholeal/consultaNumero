# Guia de Instalação - ConsultaNumero

## Instalação Rápida no Servidor

### Opção 1: Usando o Script de Instalação (Recomendado)

```bash
# Conecte-se ao servidor
ssh root@45.228.144.86

# Clone o repositório
cd /var/www/html
git clone https://github.com/mazinholeal/consultaNumero.git
cd consultanumero

# Execute o script de instalação
chmod +x install.sh
./install.sh
```

### Opção 2: Instalação Manual

```bash
# 1. Conecte-se ao servidor
ssh root@45.228.144.86

# 2. Atualize o sistema
apt-get update
apt-get install -y apache2 php php-cli python3 python3-pip curl git

# 3. Clone o repositório
cd /var/www/html
git clone https://github.com/mazinholeal/consultaNumero.git
cd consultanumero

# 4. Configure permissões
chmod 777 uploads results status
chmod +x process_batch.py
chown -R www-data:www-data /var/www/html/consultanumero

# 5. Configure Apache
a2enmod rewrite
a2enmod headers
systemctl restart apache2

# 6. Teste
python3 -m py_compile process_batch.py
```

## Verificação Pós-Instalação

```bash
# Verificar Apache
systemctl status apache2

# Verificar PHP
php -v

# Verificar Python
python3 --version

# Verificar permissões
ls -la /var/www/html/consultanumero/uploads/
ls -la /var/www/html/consultanumero/results/
ls -la /var/www/html/consultanumero/status/

# Testar acesso
curl http://localhost/consultanumero/
```

## Configuração de Firewall (se necessário)

```bash
# Permitir HTTP
ufw allow 80/tcp
ufw allow 443/tcp
ufw reload
```

## Troubleshooting

### Erro de permissão
```bash
chmod 777 uploads results status
chown -R www-data:www-data /var/www/html/consultanumero
```

### Apache não inicia
```bash
systemctl status apache2
tail -f /var/log/apache2/error.log
```

### Python não encontrado
```bash
apt-get install -y python3 python3-pip
which python3
```

### Testar script Python manualmente
```bash
cd /var/www/html/consultanumero
python3 process_batch.py /caminho/arquivo.txt job_test_123
```

## Acesso

Após a instalação, acesse:
- URL: `http://45.228.144.86/consultanumero/`
- Ou: `http://seu-dominio.com/consultanumero/`

