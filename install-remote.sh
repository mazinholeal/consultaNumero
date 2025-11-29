#!/bin/bash
# Script para instalação remota via SSH com senha
# Uso: ./install-remote.sh

set -e

SERVER="45.228.144.86"
USER="root"
PASSWORD="!Oa1234#"
INSTALL_DIR="/var/www/html/consultanumero"

echo "=========================================="
echo "Instalação Remota - ConsultaNumero"
echo "=========================================="
echo ""

# Verificar se sshpass está instalado
if ! command -v sshpass &> /dev/null; then
    echo "Instalando sshpass..."
    if command -v apt-get &> /dev/null; then
        sudo apt-get install -y sshpass
    elif command -v yum &> /dev/null; then
        sudo yum install -y sshpass
    else
        echo "Por favor, instale sshpass manualmente"
        exit 1
    fi
fi

echo "Conectando ao servidor $SERVER..."
echo ""

# Comando de instalação remoto
sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null root@$SERVER << 'ENDSSH'
# Atualizar sistema
echo "[1/8] Atualizando sistema..."
apt-get update -qq

# Instalar dependências
echo "[2/8] Instalando dependências..."
apt-get install -y apache2 php php-cli python3 python3-pip curl git

# Habilitar módulos do Apache
echo "[3/8] Configurando Apache..."
a2enmod rewrite
a2enmod headers

# Clonar repositório
echo "[4/8] Clonando repositório..."
cd /var/www/html
if [ -d "consultanumero" ]; then
    cd consultanumero
    # Verificar se remote está usando HTTPS e converter para SSH
    if git remote get-url origin 2>/dev/null | grep -q "https://"; then
        echo "Convertendo remote de HTTPS para SSH..."
        git remote set-url origin git@github.com:mazinholeal/consultaNumero.git
    fi
    git pull origin main || echo "Aviso: Não foi possível atualizar"
else
    # Tentar SSH primeiro
    if ssh -o BatchMode=yes -o ConnectTimeout=5 git@github.com 2>&1 | grep -q "successfully authenticated"; then
        echo "Usando SSH para clonar..."
        git clone git@github.com:mazinholeal/consultaNumero.git
    else
        echo "SSH não configurado. Usando HTTPS..."
        git clone https://github.com/mazinholeal/consultaNumero.git
    fi
    cd consultanumero
fi

# Criar diretórios
echo "[5/8] Criando diretórios..."
mkdir -p uploads results status
chmod 777 uploads results status

# Configurar permissões
echo "[6/8] Configurando permissões..."
chown -R www-data:www-data /var/www/html/consultanumero
chmod -R 755 /var/www/html/consultanumero
chmod 777 uploads results status
chmod +x process_batch.py

# Verificar Python
echo "[7/8] Verificando Python..."
python3 --version

# Testar script
echo "[8/8] Testando script Python..."
python3 -m py_compile process_batch.py && echo "Script Python OK" || echo "Erro no script Python"

# Reiniciar Apache
echo "Reiniciando Apache..."
systemctl restart apache2

echo ""
echo "=========================================="
echo "Instalação Concluída!"
echo "=========================================="
echo "Acesse: http://45.228.144.86/consultanumero/"
ENDSSH

echo ""
echo "Instalação remota concluída!"

