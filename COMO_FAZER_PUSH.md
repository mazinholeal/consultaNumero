# 🚀 Como Fazer Push para o GitHub

## ✅ Configuração SSH Concluída

A chave SSH foi gerada e o repositório está configurado para usar SSH.

## 📋 Passo a Passo para Fazer Push

### 1️⃣ Adicionar Chave SSH no GitHub (Fazer UMA VEZ)

**Chave pública SSH:**
```
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIOxvYEqm+OrMLp6dlPDyM2jUkNmudakV4oLrRkof5NkV consultanumero@server
```

**Como adicionar:**
1. Acesse: https://github.com/settings/keys
2. Clique em "New SSH key"
3. Cole a chave acima no campo "Key"
4. Dê um título (ex: "Consultanumero Server")
5. Clique em "Add SSH key"

### 2️⃣ Testar Conexão SSH

```bash
cd /var/www/html/consultanumero
./test-ssh.sh
```

Se aparecer "✅ Conexão SSH funcionando!", está pronto!

### 3️⃣ Fazer Push das Mudanças

**Opção A: Script Automático (Recomendado)**
```bash
cd /var/www/html/consultanumero
./git-push-auto.sh "Correção: erro 500 em arquivos grandes e recuperação de consultas"
```

**Opção B: Manual**
```bash
cd /var/www/html/consultanumero

# Ver mudanças
git status

# Adicionar arquivos (o script já ignora arquivos de dados)
git add .

# Fazer commit
git commit -m "Sua mensagem aqui"

# Fazer push
git push origin main
```

## 🔄 Automatizar Push Futuro

### Opção 1: Hook Git (Push automático após cada commit)

```bash
cd /var/www/html/consultanumero
cat > .git/hooks/post-commit << 'EOF'
#!/bin/bash
cd /var/www/html/consultanumero
./git-push-auto.sh "Commit automático"
EOF
chmod +x .git/hooks/post-commit
```

### Opção 2: Cron Job (Push diário)

```bash
crontab -e
```

Adicione:
```bash
# Push diário às 2h da manhã
0 2 * * * cd /var/www/html/consultanumero && ./git-push-auto.sh "Backup diário automático" >> /var/log/git-push.log 2>&1
```

## 📝 Arquivos que NÃO serão Commitados

O script automaticamente ignora:
- ✅ `database/*.json` - Histórico de consultas
- ✅ `results/*.json` - Resultados das consultas
- ✅ `status/*.json` - Status e checkpoints
- ✅ `uploads/*.txt` e `uploads/*.csv` - Arquivos enviados

## ⚠️ Importante

- **Sempre teste SSH primeiro:** `./test-ssh.sh`
- **Revise mudanças antes de push:** `git status`
- **Use mensagens descritivas** nos commits
- **Arquivos de dados nunca são commitados** (protegidos pelo script)

## 🆘 Problemas?

**Erro: "Permission denied (publickey)"**
- A chave SSH não foi adicionada ao GitHub
- Execute: `./test-ssh.sh` para ver a chave novamente

**Erro: "fatal: not a git repository"**
- Execute: `cd /var/www/html/consultanumero`

**Erro: "Could not resolve hostname"**
- Verifique conexão com internet
- Teste: `ping github.com`

