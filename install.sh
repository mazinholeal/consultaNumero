    #!/bin/bash
    # Script de instalação do projeto ConsultaNumero
    # Execute este script no servidor como root ou com sudo

    set -e

    echo "=========================================="
    echo "Instalação do Sistema ConsultaNumero"
    echo "=========================================="
    echo ""

    # Cores para output
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    NC='\033[0m' # No Color

    # Verificar se está rodando como root
    if [ "$EUID" -ne 0 ]; then 
        echo -e "${RED}Por favor, execute como root ou com sudo${NC}"
        exit 1
    fi

    # Diretório de instalação
    INSTALL_DIR="/var/www/html/consultanumero"

    echo "Diretório de instalação: $INSTALL_DIR"
    echo ""

    # Atualizar sistema
    echo -e "${YELLOW}[1/8] Atualizando sistema...${NC}"
    apt-get update -qq

    # Instalar dependências
    echo -e "${YELLOW}[2/8] Instalando dependências...${NC}"
    apt-get install -y apache2 php php-cli python3 python3-pip curl git

    # Habilitar módulos do Apache
    echo -e "${YELLOW}[3/8] Configurando Apache...${NC}"
    a2enmod rewrite
    a2enmod headers

# Clonar ou atualizar repositório
echo -e "${YELLOW}[4/8] Clonando/Atualizando repositório do GitHub...${NC}"
if [ -d "$INSTALL_DIR" ]; then
    echo "Diretório já existe. Atualizando código..."
    cd "$INSTALL_DIR"
    
    # Verificar se é um repositório git válido
    if [ -d ".git" ]; then
        # Fazer backup de mudanças locais se houver
        if ! git diff --quiet || ! git diff --cached --quiet; then
            echo -e "${YELLOW}Aviso: Há mudanças locais. Fazendo stash...${NC}"
            git stash save "Backup antes de atualizar - $(date '+%Y-%m-%d %H:%M:%S')"
        fi
        
        # Verificar se remote está configurado
        if git remote get-url origin 2>/dev/null | grep -q "https://"; then
            echo "Convertendo remote de HTTPS para SSH (se SSH estiver configurado)..."
            # Tentar SSH primeiro
            if ssh -o BatchMode=yes -o ConnectTimeout=5 git@github.com 2>&1 | grep -q "successfully authenticated"; then
                git remote set-url origin git@github.com:mazinholeal/consultaNumero.git
            fi
        fi
        
        # Atualizar código
        echo "Buscando atualizações do GitHub..."
        git fetch origin main
        git pull origin main || {
            echo -e "${RED}Erro ao atualizar. Tentando resetar...${NC}"
            git reset --hard origin/main
        }
        echo -e "${GREEN}Código atualizado com sucesso!${NC}"
    else
        echo -e "${RED}Diretório existe mas não é um repositório git válido!${NC}"
        echo "Fazendo backup e clonando novamente..."
        mv "$INSTALL_DIR" "${INSTALL_DIR}.backup.$(date +%Y%m%d_%H%M%S)"
        mkdir -p "$(dirname $INSTALL_DIR)"
        git clone https://github.com/mazinholeal/consultaNumero.git "$INSTALL_DIR"
    fi
else
    mkdir -p "$(dirname $INSTALL_DIR)"
    # Tentar SSH primeiro (não pede senha se chave estiver configurada)
    if ssh -o BatchMode=yes -o ConnectTimeout=5 git@github.com 2>&1 | grep -q "successfully authenticated"; then
        echo "Usando SSH para clonar (sem senha)..."
        git clone git@github.com:mazinholeal/consultaNumero.git "$INSTALL_DIR"
    else
        echo "Usando HTTPS para clonar (repositório público - não pede senha)..."
        git clone https://github.com/mazinholeal/consultaNumero.git "$INSTALL_DIR"
    fi
fi

    # Criar diretórios necessários
    echo -e "${YELLOW}[5/8] Criando diretórios...${NC}"
    cd "$INSTALL_DIR"
    mkdir -p uploads results status
    chmod 777 uploads results status

    # Configurar permissões
    echo -e "${YELLOW}[6/8] Configurando permissões...${NC}"
    chown -R www-data:www-data "$INSTALL_DIR"
    chmod -R 755 "$INSTALL_DIR"
    chmod 777 uploads results status
    chmod +x process_batch.py

    # Verificar Python
    echo -e "${YELLOW}[7/8] Verificando Python...${NC}"
    python3 --version
    if ! command -v python3 &> /dev/null; then
        echo -e "${RED}Python3 não encontrado!${NC}"
        exit 1
    fi

    # Testar script Python
    echo -e "${YELLOW}[8/8] Testando script Python...${NC}"
    python3 -m py_compile process_batch.py && echo -e "${GREEN}Script Python OK${NC}" || echo -e "${RED}Erro no script Python${NC}"

    # Configurar Apache
    echo ""
    echo -e "${YELLOW}Configurando Apache...${NC}"
    if [ -f /etc/apache2/sites-available/000-default.conf ]; then
        # Verificar se já tem configuração
        if ! grep -q "consultanumero" /etc/apache2/sites-available/000-default.conf; then
            echo "DocumentRoot já configurado"
        fi
    fi

    # Reiniciar Apache
    echo -e "${YELLOW}Reiniciando Apache...${NC}"
    systemctl restart apache2

    # Verificar status
    echo ""
    echo -e "${GREEN}=========================================="
    echo "Instalação Concluída!"
    echo "==========================================${NC}"
    echo ""
    echo "Informações:"
    echo "  - Diretório: $INSTALL_DIR"
    echo "  - URL: http://$(hostname -I | awk '{print $1}')/consultanumero/"
    echo "  - Permissões configuradas"
    echo ""
    echo "Próximos passos:"
    echo "  1. Acesse: http://$(hostname -I | awk '{print $1}')/consultanumero/"
    echo "  2. Teste o upload de um arquivo"
    echo "  3. Verifique os logs em caso de erro: tail -f /var/log/apache2/error.log"
    echo ""

