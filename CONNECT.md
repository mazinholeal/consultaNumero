# Guia de Conexão SSH

## Problemas Comuns com Senha SSH

### 1. Caracteres Especiais na Senha

A senha `!Oa1234#` contém caracteres especiais que podem causar problemas:

**Solução 1: Usar aspas simples**
```bash
ssh root@45.228.144.86
# Quando pedir a senha, digite: !Oa1234#
```

**Solução 2: Escapar caracteres especiais**
```bash
# No bash, alguns caracteres precisam ser escapados
ssh root@45.228.144.86
# Digite a senha normalmente quando solicitado
```

**Solução 3: Usar sshpass (automação)**
```bash
# Instalar sshpass
sudo apt-get install sshpass

# Conectar com senha
sshpass -p '!Oa1234#' ssh root@45.228.144.86
```

### 2. Problema de Layout de Teclado

Se estiver usando teclado diferente (US vs BR):
- Verifique se está digitando no layout correto
- Tente copiar e colar a senha diretamente

### 3. Verificar se o SSH está ativo no servidor

```bash
# Testar conexão
ping 45.228.144.86

# Verificar porta SSH
telnet 45.228.144.86 22
# ou
nc -zv 45.228.144.86 22
```

### 4. Usar Chave SSH (Mais Seguro)

```bash
# Gerar chave SSH localmente
ssh-keygen -t ed25519 -C "seu_email@example.com"

# Copiar chave para o servidor
ssh-copy-id root@45.228.144.86
# Digite a senha uma vez

# Depois disso, não precisará mais de senha
ssh root@45.228.144.86
```

### 5. Script Automatizado

Use o script `install-remote.sh` que já inclui a senha:

```bash
chmod +x install-remote.sh
./install-remote.sh
```

## Comandos Diretos para Teste

### Teste de Conexão Básico
```bash
ssh -v root@45.228.144.86
```

### Teste com Timeout
```bash
ssh -o ConnectTimeout=10 root@45.228.144.86
```

### Teste com Verbose (mostra detalhes)
```bash
ssh -vvv root@45.228.144.86
```

## Alternativa: Instalação Manual via SCP

Se SSH não funcionar, você pode:

1. Fazer upload dos arquivos via SCP:
```bash
scp -r /var/www/html/consultanumero root@45.228.144.86:/var/www/html/
```

2. Depois conectar e executar:
```bash
ssh root@45.228.144.86
cd /var/www/html/consultanumero
chmod +x install.sh
./install.sh
```

## Dicas Importantes

- **Copiar e Colar**: Tente copiar a senha `!Oa1234#` e colar diretamente no terminal
- **Sem Espaços**: Certifique-se de não ter espaços antes ou depois da senha
- **Caps Lock**: Verifique se Caps Lock está desativado
- **Terminal**: Alguns terminais não mostram caracteres ao digitar senha (é normal)

