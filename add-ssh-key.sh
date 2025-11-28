#!/bin/bash
# Script para adicionar chave SSH ao GitHub

echo "=========================================="
echo "Chave SSH Pública Gerada!"
echo "=========================================="
echo ""
echo "Sua chave pública SSH:"
echo ""
cat ~/.ssh/id_ed25519.pub
echo ""
echo "=========================================="
echo "INSTRUÇÕES:"
echo "=========================================="
echo "1. Copie a chave acima (todo o conteúdo)"
echo "2. Acesse: https://github.com/settings/keys"
echo "3. Clique em 'New SSH key'"
echo "4. Cole a chave e salve"
echo "5. Depois execute: git push -u origin main"
echo ""
echo "Ou execute este script novamente após adicionar a chave."
echo ""

