# 🚀 Instalação no Servidor - Guia Rápido

## ✅ Repositório Público - Instalação Simplificada

Agora que o repositório é **público**, a instalação é muito mais simples!

## 📋 Passo a Passo

### 1. Conecte no servidor
```bash
ssh root@45.228.144.86
```

### 2. Execute o script de instalação
```bash
cd /var/www/html
git clone https://github.com/mazinholeal/consultaNumero.git
cd consultanumero
chmod +x install.sh
./install.sh
```

**Pronto!** Não precisa de senha para clonar (repositório público) ✅

## 🔧 O que o script faz automaticamente:

1. ✅ Atualiza o sistema
2. ✅ Instala Apache, PHP, Python3
3. ✅ Configura módulos do Apache
4. ✅ Clona o repositório (sem senha - público)
5. ✅ Cria diretórios necessários
6. ✅ Configura permissões
7. ✅ Testa o script Python
8. ✅ Reinicia o Apache

## 🌐 Acessar após instalação

```
http://45.228.144.86/consultanumero/
```

## 🔍 Verificar instalação

```bash
# Verificar Apache
systemctl status apache2

# Verificar PHP
php -v

# Verificar Python
python3 --version

# Verificar permissões
ls -la /var/www/html/consultanumero/uploads/
```

## 📝 Notas

- **Repositório público**: Clone funciona sem autenticação
- **Push ainda precisa SSH**: Para fazer push no servidor, configure SSH
- **Script automático**: Tudo é configurado automaticamente

## 🆘 Troubleshooting

### Erro de permissão
```bash
chmod 777 uploads results status
chown -R www-data:www-data /var/www/html/consultanumero
```

### Apache não inicia
```bash
tail -f /var/log/apache2/error.log
systemctl restart apache2
```

### Testar manualmente
```bash
cd /var/www/html/consultanumero
python3 process_batch.py /caminho/arquivo.txt job_test_123
```

