#!/bin/bash
# Script automático para fazer push das mudanças para o GitHub via SSH
# Uso: ./git-push-auto.sh [mensagem do commit]

set -e

INSTALL_DIR="/var/www/html/consultanumero"
cd "$INSTALL_DIR"

# Configurar safe.directory se necessário
git config --global --add safe.directory "$INSTALL_DIR" 2>/dev/null || true

# Verificar se SSH está configurado
if ! git remote get-url origin 2>/dev/null | grep -q "git@github.com"; then
    echo "⚠️  Configurando SSH..."
    
    # Verificar se chave SSH existe
    if [ ! -f ~/.ssh/id_ed25519_github ]; then
        echo "❌ Chave SSH não encontrada!"
        echo "Execute primeiro: ssh-keygen -t ed25519 -C 'seu_email@example.com' -f ~/.ssh/id_ed25519_github"
        exit 1
    fi
    
    # Configurar remote para SSH
    git remote set-url origin git@github.com:mazinholeal/consultaNumero.git
    echo "✅ Remote configurado para SSH"
fi

# Verificar se há mudanças
if git diff --quiet && git diff --cached --quiet; then
    echo "✅ Nenhuma mudança para commitar"
    exit 0
fi

# Adicionar todos os arquivos modificados (exceto arquivos de dados)
echo "📝 Adicionando arquivos..."
git add -A

# Ignorar arquivos de dados que não devem ser commitados
git reset -- database/*.json results/*.json status/*.json uploads/*.txt uploads/*.csv 2>/dev/null || true

# Verificar se ainda há algo para commitar após filtrar
if git diff --cached --quiet; then
    echo "✅ Nenhuma mudança relevante para commitar (arquivos de dados ignorados)"
    exit 0
fi

# Mensagem do commit
COMMIT_MSG="${1:-Atualização automática - $(date '+%Y-%m-%d %H:%M:%S')}"

echo "💾 Fazendo commit: $COMMIT_MSG"
git commit -m "$COMMIT_MSG" || {
    echo "⚠️  Nenhuma mudança para commitar"
    exit 0
}

# Fazer push
echo "🚀 Fazendo push para GitHub..."
git push origin main || {
    echo "❌ Erro ao fazer push. Verifique:"
    echo "   1. Chave SSH adicionada ao GitHub: https://github.com/settings/keys"
    echo "   2. Teste: ssh -T git@github.com"
    exit 1
}

echo "✅ Push concluído com sucesso!"
echo ""
echo "📊 Status:"
git log -1 --oneline

