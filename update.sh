#!/bin/bash
# Script rápido para atualizar o projeto
# Uso: ./update.sh

set -e

INSTALL_DIR="/var/www/html/consultanumero"

echo "=========================================="
echo "Atualizando ConsultaNumero"
echo "=========================================="
echo ""

if [ ! -d "$INSTALL_DIR" ]; then
    echo "Erro: Diretório $INSTALL_DIR não encontrado!"
    echo "Execute install.sh primeiro para instalar o projeto."
    exit 1
fi

cd "$INSTALL_DIR"

if [ ! -d ".git" ]; then
    echo "Erro: Não é um repositório git válido!"
    exit 1
fi

# Fazer backup de mudanças locais
if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "Fazendo backup de mudanças locais..."
    git stash save "Backup antes de atualizar - $(date '+%Y-%m-%d %H:%M:%S')"
fi

# Atualizar
echo "Buscando atualizações..."
git fetch origin main

# Verificar se há atualizações
LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse origin/main)

if [ "$LOCAL" = "$REMOTE" ]; then
    echo "✅ Já está na versão mais recente!"
    exit 0
fi

echo "Atualizando código..."
git pull origin main || {
    echo "Conflitos detectados. Fazendo reset para versão remota..."
    git reset --hard origin/main
}

echo ""
echo "✅ Atualização concluída!"
echo ""
echo "Para ver mudanças locais que foram salvas:"
echo "  git stash list"
echo ""
echo "Para restaurar mudanças locais:"
echo "  git stash pop"

