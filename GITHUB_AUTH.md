# Autenticação GitHub - Guia Completo

## ✅ Status Atual

Seu repositório já está configurado com **SSH** e funcionando!

A mensagem `Hi mazinholeal! You've successfully authenticated` confirma que está tudo OK.

## Por que GitHub não aceita mais senha?

Desde agosto de 2021, o GitHub **não aceita mais senhas** para autenticação HTTPS. Você precisa usar:

1. **SSH** (já configurado ✅)
2. **Personal Access Token** (para HTTPS)

## Verificar Configuração Atual

```bash
# Ver remote configurado
git remote -v

# Deve mostrar:
# origin  git@github.com:mazinholeal/consultaNumero.git (fetch)
# origin  git@github.com:mazinholeal/consultaNumero.git (push)
```

## Se ainda pedir senha

### Problema 1: Remote está usando HTTPS em vez de SSH

**Solução:**
```bash
git remote set-url origin git@github.com:mazinholeal/consultaNumero.git
```

### Problema 2: Chave SSH não está no GitHub

**Solução:**
1. Mostre sua chave pública:
```bash
cat ~/.ssh/id_ed25519.pub
```

2. Copie a chave e adicione em:
   - https://github.com/settings/keys
   - Clique em "New SSH key"
   - Cole a chave e salve

### Problema 3: Quer usar HTTPS com Token

**Solução:**
1. Crie um Personal Access Token:
   - https://github.com/settings/tokens
   - "Generate new token" → "Generate new token (classic)"
   - Selecione escopo `repo`
   - Copie o token

2. Configure o remote:
```bash
git remote set-url origin https://SEU_TOKEN@github.com/mazinholeal/consultaNumero.git
```

## Testar Autenticação SSH

```bash
ssh -T git@github.com
```

**Resposta esperada:**
```
Hi mazinholeal! You've successfully authenticated, but GitHub does not provide shell access.
```

Isso significa que está funcionando! ✅

## Comandos Git Básicos

```bash
# Ver status
git status

# Adicionar arquivos
git add .

# Commit
git commit -m "sua mensagem"

# Push (não deve pedir senha se SSH estiver configurado)
git push

# Pull
git pull
```

## Troubleshooting

### Erro: "Permission denied (publickey)"

**Solução:**
1. Verifique se a chave está no GitHub
2. Teste: `ssh -T git@github.com`
3. Se não funcionar, adicione a chave novamente

### Erro: "Could not read from remote repository"

**Solução:**
```bash
# Verificar remote
git remote -v

# Se estiver HTTPS, mude para SSH
git remote set-url origin git@github.com:mazinholeal/consultaNumero.git
```

### Git ainda pede senha mesmo com SSH

**Solução:**
```bash
# Verificar se está usando SSH
git remote -v

# Se não estiver, configure:
git remote set-url origin git@github.com:mazinholeal/consultaNumero.git

# Testar novamente
git push
```

