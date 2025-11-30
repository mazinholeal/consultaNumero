#!/bin/bash
# Script para testar conexão SSH com GitHub

echo "🔍 Testando conexão SSH com GitHub..."
echo ""

# Verificar se chave existe
if [ ! -f ~/.ssh/id_ed25519_github ]; then
    echo "❌ Chave SSH não encontrada!"
    exit 1
fi

# Testar conexão
echo "Testando: ssh -T git@github.com"
echo ""

ssh -T git@github.com 2>&1

EXIT_CODE=$?

echo ""
if [ $EXIT_CODE -eq 0 ] || echo "$?" | grep -q "successfully authenticated"; then
    echo "✅ Conexão SSH funcionando!"
    echo ""
    echo "Você pode agora fazer push usando:"
    echo "  ./git-push-auto.sh"
else
    echo "❌ Conexão SSH falhou!"
    echo ""
    echo "📋 Próximos passos:"
    echo "1. Adicione a chave SSH pública ao GitHub:"
    echo "   https://github.com/settings/keys"
    echo ""
    echo "2. Chave pública:"
    cat ~/.ssh/id_ed25519_github.pub
    echo ""
    echo "3. Depois execute novamente: ./test-ssh.sh"
fi

