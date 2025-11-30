# 🔐 Configuração SSH para GitHub

## ✅ Chave SSH Gerada

A chave SSH foi gerada automaticamente. Agora você precisa adicioná-la ao GitHub:

### 📋 Chave Pública SSH:

```
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIOxvYEqm+OrMLp6dlPDyM2jUkNmudakV4oLrRkof5NkV consultanumero@server
```

### 🔧 Como Adicionar no GitHub:

1. **Copie a chave acima** (toda a linha)

2. **Acesse:** https://github.com/settings/keys

3. **Clique em:** "New SSH key"

4. **Preencha:**
   - **Title:** `Consultanumero Server` (ou qualquer nome)
   - **Key:** Cole a chave copiada acima
   - **Key type:** Authentication Key

5. **Clique em:** "Add SSH key"

### ✅ Testar Conexão:

Após adicionar a chave, teste:

```bash
ssh -T git@github.com
```

Você deve ver: `Hi mazinholeal! You've successfully authenticated...`

## 🚀 Usar o Script Automático de Push

Após configurar a chave SSH no GitHub, você pode usar:

```bash
cd /var/www/html/consultanumero
./git-push-auto.sh "Sua mensagem de commit aqui"
```

Ou sem mensagem (usa mensagem padrão):

```bash
./git-push-auto.sh
```

## 📝 O que o Script Faz:

1. ✅ Verifica se SSH está configurado
2. ✅ Adiciona arquivos modificados
3. ✅ Ignora arquivos de dados (results, status, uploads, database)
4. ✅ Faz commit com mensagem
5. ✅ Faz push para GitHub

## 🔄 Atualizações Automáticas Futuras

Para automatizar completamente, você pode:

1. **Criar um cron job** para push automático:
```bash
# Editar crontab
crontab -e

# Adicionar linha (push diário às 2h da manhã)
0 2 * * * cd /var/www/html/consultanumero && ./git-push-auto.sh "Backup diário automático" >> /var/log/git-push.log 2>&1
```

2. **Ou criar um hook Git** para push automático após commits:
```bash
# Criar hook post-commit
cat > /var/www/html/consultanumero/.git/hooks/post-commit << 'EOF'
#!/bin/bash
cd /var/www/html/consultanumero
./git-push-auto.sh "Commit automático"
EOF
chmod +x /var/www/html/consultanumero/.git/hooks/post-commit
```

## ⚠️ Importante

- O script **NÃO** commita arquivos de dados (results, status, uploads, database)
- Sempre revise as mudanças antes de fazer push
- Use mensagens de commit descritivas

