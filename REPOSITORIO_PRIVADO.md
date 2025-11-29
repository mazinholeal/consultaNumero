# Repositório Privado vs Público

## 🔒 Repositório PRIVADO

Se o repositório for **privado**, você precisa de autenticação mesmo para clonar:

### Opção 1: Usar SSH (Recomendado)

**No servidor, configure SSH primeiro:**

```bash
# 1. Gerar chave SSH no servidor
ssh-keygen -t ed25519 -C "servidor@consultanumero" -f ~/.ssh/id_ed25519 -N ""

# 2. Mostrar chave pública
cat ~/.ssh/id_ed25519.pub

# 3. Adicionar chave no GitHub:
#    - Acesse: https://github.com/settings/keys
#    - Clique em "New SSH key"
#    - Cole a chave e salve

# 4. Testar
ssh -T git@github.com

# 5. Clonar usando SSH (não pede senha)
git clone git@github.com:mazinholeal/consultaNumero.git
```

### Opção 2: Usar Personal Access Token

```bash
# 1. Criar token: https://github.com/settings/tokens
# 2. Clonar com token
git clone https://SEU_TOKEN@github.com/mazinholeal/consultaNumero.git
```

## 🌐 Repositório PÚBLICO

Se o repositório for **público**, pode clonar sem autenticação:

```bash
# Funciona sem senha
git clone https://github.com/mazinholeal/consultaNumero.git
```

## 🔍 Como Verificar se é Privado?

1. Acesse: https://github.com/mazinholeal/consultaNumero
2. Se aparecer um cadeado 🔒 = **PRIVADO**
3. Se não aparecer cadeado = **PÚBLICO**

## ✅ Solução Rápida para Servidor

**Configure SSH no servidor antes de instalar:**

```bash
# No servidor (45.228.144.86)
ssh root@45.228.144.86

# Gerar chave SSH
ssh-keygen -t ed25519 -C "servidor" -f ~/.ssh/id_ed25519 -N ""

# Mostrar chave
cat ~/.ssh/id_ed25519.pub

# Copie a chave e adicione no GitHub: https://github.com/settings/keys

# Depois execute o script de instalação
cd /var/www/html
git clone git@github.com:mazinholeal/consultaNumero.git
cd consultanumero
chmod +x install.sh
./install.sh
```

## 📝 Script Atualizado

O script `install.sh` agora:
- ✅ Tenta usar SSH primeiro (se configurado)
- ✅ Usa HTTPS como fallback
- ✅ Converte remote para SSH automaticamente

## 🎯 Recomendação

**Para repositório privado:**
1. Configure SSH no servidor
2. Adicione a chave no GitHub
3. Use `git clone git@github.com:...` (não pede senha)

**Para repositório público:**
- Pode usar HTTPS normalmente sem senha

