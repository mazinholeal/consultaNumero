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

# IMPORTANTE: Fazer backup do arquivo de consultas ANTES de qualquer operação git
# Este arquivo não está no git e pode ser perdido em git reset --hard
if [ -f "$INSTALL_DIR/database/consultas.json" ] && [ -s "$INSTALL_DIR/database/consultas.json" ]; then
    BACKUP_FILE="$INSTALL_DIR/database/consultas.json.backup.$(date +%Y%m%d_%H%M%S)"
    echo "Fazendo backup do histórico de consultas..."
    cp "$INSTALL_DIR/database/consultas.json" "$BACKUP_FILE"
    echo "Backup salvo em: $BACKUP_FILE"
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
    # Restaurar consultas.json se foi perdido acidentalmente
    if [ ! -f "$INSTALL_DIR/database/consultas.json" ] || [ ! -s "$INSTALL_DIR/database/consultas.json" ]; then
        LATEST_BACKUP=$(ls -t "$INSTALL_DIR/database/consultas.json.backup."* 2>/dev/null | head -1)
        if [ -n "$LATEST_BACKUP" ] && [ -f "$LATEST_BACKUP" ]; then
            echo "Restaurando histórico de consultas do backup mais recente..."
            cp "$LATEST_BACKUP" "$INSTALL_DIR/database/consultas.json"
            chmod 666 "$INSTALL_DIR/database/consultas.json"
            chown www-data:www-data "$INSTALL_DIR/database/consultas.json"
        fi
    fi
    exit 0
fi

echo "Atualizando código..."
git pull origin main || {
    echo "Conflitos detectados. Fazendo reset para versão remota..."
    git reset --hard origin/main
    
    # Restaurar consultas.json após reset (se backup existe)
    if [ ! -f "$INSTALL_DIR/database/consultas.json" ] || [ ! -s "$INSTALL_DIR/database/consultas.json" ]; then
        LATEST_BACKUP=$(ls -t "$INSTALL_DIR/database/consultas.json.backup."* 2>/dev/null | head -1)
        if [ -n "$LATEST_BACKUP" ] && [ -f "$LATEST_BACKUP" ]; then
            echo "Restaurando histórico de consultas do backup após reset..."
            cp "$LATEST_BACKUP" "$INSTALL_DIR/database/consultas.json"
            chmod 666 "$INSTALL_DIR/database/consultas.json"
            chown www-data:www-data "$INSTALL_DIR/database/consultas.json"
        else
            echo "⚠️  Aviso: Arquivo consultas.json não encontrado e nenhum backup disponível."
            echo "Criando arquivo vazio..."
            echo '{}' > "$INSTALL_DIR/database/consultas.json"
            chmod 666 "$INSTALL_DIR/database/consultas.json"
            chown www-data:www-data "$INSTALL_DIR/database/consultas.json"
        fi
    fi
}

echo ""
echo "✅ Atualização concluída!"
echo ""
echo "Para ver mudanças locais que foram salvas:"
echo "  git stash list"
echo ""
echo "Para restaurar mudanças locais:"
echo "  git stash pop"

