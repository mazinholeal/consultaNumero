# Configuração do Git para Push sem Senha

## Opção 1: Usar Personal Access Token (Recomendado)

1. Crie um token no GitHub:
   - Acesse: https://github.com/settings/tokens
   - Clique em "Generate new token" → "Generate new token (classic)"
   - Dê um nome e selecione o escopo `repo`
   - Copie o token gerado

2. Configure o remote com o token:
```bash
git remote set-url origin https://SEU_TOKEN_AQUI@github.com/mazinholeal/consultaNumero.git
```

3. Faça o push:
```bash
git push -u origin main
```

## Opção 2: Usar o Script Helper

```bash
GITHUB_TOKEN=seu_token_aqui ./git-push.sh
```

## Opção 3: Configurar SSH (Mais Seguro)

1. Gere uma chave SSH (se ainda não tiver):
```bash
ssh-keygen -t ed25519 -C "seu_email@example.com"
```

2. Adicione a chave pública ao GitHub:
```bash
cat ~/.ssh/id_ed25519.pub
```
   - Copie a saída e adicione em: https://github.com/settings/keys

3. Configure o remote para SSH:
```bash
git remote set-url origin git@github.com:mazinholeal/consultaNumero.git
```

4. Teste a conexão:
```bash
ssh -T git@github.com
```

5. Faça o push:
```bash
git push -u origin main
```

## Opção 4: Credential Helper (Armazena credenciais)

```bash
git config credential.helper store
git push -u origin main
# Digite seu usuário e token quando solicitado (será salvo)
```

