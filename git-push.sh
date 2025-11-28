#!/bin/bash
# Script para fazer push sem pedir senha usando token do GitHub

# Se você tem um token do GitHub, use assim:
# GITHUB_TOKEN=seu_token_aqui ./git-push.sh

if [ -z "$GITHUB_TOKEN" ]; then
    echo "Para usar este script sem pedir senha:"
    echo "1. Crie um Personal Access Token no GitHub: https://github.com/settings/tokens"
    echo "2. Execute: GITHUB_TOKEN=seu_token ./git-push.sh"
    echo ""
    echo "Ou configure manualmente:"
    echo "git remote set-url origin https://SEU_TOKEN@github.com/mazinholeal/consultaNumero.git"
    exit 1
fi

# Configura o remote com o token
git remote set-url origin https://${GITHUB_TOKEN}@github.com/mazinholeal/consultaNumero.git

# Faz o push
git push -u origin main

echo "Push concluído!"

