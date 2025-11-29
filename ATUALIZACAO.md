# 🔄 Como Atualizar o Projeto

## Opção 1: Rodar o Script de Instalação Novamente (Recomendado)

```bash
cd /var/www/html/consultanumero
./install.sh
```

**O que acontece:**
- ✅ Detecta que o diretório já existe
- ✅ Faz backup automático de mudanças locais
- ✅ Atualiza o código do GitHub
- ✅ Mantém configurações e permissões
- ✅ Não reinstala dependências desnecessariamente

## Opção 2: Script Rápido de Atualização

```bash
cd /var/www/html/consultanumero
./update.sh
```

**Mais rápido**, apenas atualiza o código sem reinstalar dependências.

## Opção 3: Atualização Manual

```bash
cd /var/www/html/consultanumero

# Ver mudanças locais (se houver)
git status

# Fazer backup de mudanças locais
git stash

# Atualizar
git pull origin main

# Se houver conflitos
git reset --hard origin/main
```

## 📋 O que é Preservado na Atualização

✅ **Preservado:**
- Arquivos de upload (`uploads/`)
- Resultados (`results/`)
- Status e checkpoints (`status/`)
- Configurações do Apache
- Permissões dos diretórios

❌ **Atualizado:**
- Código PHP
- Scripts Python
- Arquivos de configuração (.htaccess, etc)
- Documentação

## 🔍 Verificar Versão Atual

```bash
cd /var/www/html/consultanumero
git log -1 --oneline
git status
```

## ⚠️ Se Houver Conflitos

O script automaticamente:
1. Faz backup das mudanças locais
2. Reseta para a versão do GitHub
3. Você pode restaurar depois com `git stash pop`

## 🚀 Recomendação

**Para atualizações simples:** Use `./update.sh`  
**Para atualizações completas:** Use `./install.sh`

Ambos funcionam! O `install.sh` é mais completo e verifica tudo.

