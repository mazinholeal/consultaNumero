#!/bin/bash
# Script para instalar php-sqlite3 e configurar permissões
# Execute: sudo ./install_sqlite.sh

set -e

echo "=========================================="
echo "Instalando PHP SQLite3"
echo "=========================================="
echo ""

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then 
    echo "Por favor, execute como root ou com sudo:"
    echo "  sudo ./install_sqlite.sh"
    exit 1
fi

# Atualizar pacotes
echo "[1/4] Atualizando pacotes..."
apt-get update -qq

# Instalar php-sqlite3
echo "[2/4] Instalando php-sqlite3..."
apt-get install -y php-sqlite3

# Verificar instalação
echo "[3/4] Verificando instalação..."
if php -m | grep -q sqlite; then
    echo "✅ Módulo SQLite instalado com sucesso!"
else
    echo "❌ Erro: Módulo não encontrado após instalação"
    exit 1
fi

# Configurar permissões
echo "[4/4] Configurando permissões..."
INSTALL_DIR="/var/www/html/consultanumero"
if [ -d "$INSTALL_DIR/database" ]; then
    chmod 777 "$INSTALL_DIR/database"
    if [ -f "$INSTALL_DIR/database/consultas.db" ]; then
        chmod 666 "$INSTALL_DIR/database/consultas.db"
    fi
    chown -R www-data:www-data "$INSTALL_DIR/database" 2>/dev/null || true
    echo "✅ Permissões configuradas"
fi

# Reiniciar Apache
echo ""
echo "Reiniciando Apache..."
systemctl restart apache2

echo ""
echo "=========================================="
echo "Instalação Concluída!"
echo "=========================================="
echo ""
echo "Agora você pode:"
echo "  1. Acessar: http://localhost/consultanumero/historico.php"
echo "  2. Executar migração: http://localhost/consultanumero/migrate_web.php"
echo ""

