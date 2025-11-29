# ConsultaNumero - Sistema de Consulta em Lote

Sistema para consulta de números telefônicos em lote via API, com interface PHP e backend Python.

## 🚀 Funcionalidades

- ✅ **Consulta Individual**: Consulta um ou múltiplos números diretamente
- ✅ **Consulta em Lote**: Upload de arquivo CSV/TXT com múltiplos números
- ✅ **Processamento Assíncrono**: Processamento em background via Python
- ✅ **Histórico Completo**: Todas as consultas são armazenadas e podem ser visualizadas
- ✅ **Barra de Progresso Colorida**: Acompanhamento visual com cores da TIM
- ✅ **Tratamento de Erros**: Sistema robusto com retry e checkpoint
- ✅ **Paginação de Resultados**: Visualização organizada com 10, 25 ou 50 itens por página
- ✅ **Download de Resultados**: Exportação em JSON ou CSV

## 📋 Requisitos

- Apache 2.4+
- PHP 7.4+ (php-cli)
- Python 3.6+
- Git

## 🔧 Instalação

### Instalação Automática

O script de instalação faz tudo automaticamente, incluindo o clone do repositório:

```bash
cd /var/www/html
wget https://raw.githubusercontent.com/mazinholeal/consultaNumero/main/install.sh
chmod +x install.sh
sudo ./install.sh
```

**OU** se já tiver o script localmente:

```bash
sudo ./install.sh
```

O script automaticamente:
- ✅ Clona o repositório do GitHub (ou atualiza se já existir)
- ✅ Instala todas as dependências
- ✅ Configura permissões
- ✅ Configura Apache

### Instalação Manual

```bash
# Instalar dependências
sudo apt-get update
sudo apt-get install -y apache2 php php-cli python3 python3-pip curl git

# Habilitar módulos Apache
sudo a2enmod rewrite
sudo a2enmod headers

# Clonar repositório
cd /var/www/html
git clone https://github.com/mazinholeal/consultaNumero.git
cd consultanumero

# Criar diretórios e configurar permissões
mkdir -p uploads results status database
chmod 777 uploads results status database
chmod +x process_batch.py
chown -R www-data:www-data /var/www/html/consultanumero

# Reiniciar Apache
sudo systemctl restart apache2
```

## 📁 Estrutura do Projeto

```
consultanumero/
├── index.php              # Interface principal
├── upload.php             # Upload de arquivos
├── consult.php            # Consulta individual
├── status.php             # Status do processamento
├── results.php            # Visualização de resultados
├── historico.php          # Histórico de consultas
├── database.php           # Gerenciamento de histórico (JSON)
├── process_batch.py       # Script Python de processamento
├── install.sh             # Script de instalação
├── update.sh              # Script de atualização
├── .htaccess              # Configurações Apache
├── uploads/               # Arquivos enviados
├── results/               # Resultados JSON
├── status/                # Status e checkpoints
└── database/              # Histórico em JSON
```

## 🎨 Design

- **Framework CSS**: Tailwind CSS
- **Cores**: Identidade visual TIM
  - Azul: `#004C97`
  - Vermelho: `#E30613`
  - Amarelo: `#FFD100`
- **Logo**: TIM.png

## 📊 Armazenamento

O sistema usa **arquivos JSON** para armazenar o histórico de consultas:
- `database/consultas.json` - Histórico completo
- `results/{job_id}.json` - Resultados de cada consulta
- `status/{job_id}.json` - Status e progresso

Não é necessário banco de dados SQLite ou MySQL.

## 🔄 Atualização

Para atualizar o projeto:

```bash
cd /var/www/html/consultanumero
./update.sh
```

Ou execute o script de instalação novamente:

```bash
./install.sh
```

## 📝 Formato de Arquivo

### CSV/TXT para Consulta em Lote

- Um número por linha, ou
- Números separados por vírgula
- Tamanho máximo: 10MB
- Extensões: `.csv` ou `.txt`

**Exemplo:**
```
11941900123
81981562716
11987654321
```

ou

```
11941900123,81981562716,11987654321
```

## 🔍 API Externa

O sistema consulta a API:
```
POST https://painel.tridtelecom.com.br/_7port/consulta.php
```

## 🛠️ Troubleshooting

### Erro de Upload
- Verificar permissões: `chmod 777 uploads results status database`
- Verificar limites PHP: `php -i | grep upload_max_filesize`

### Erro de Processamento
- Verificar Python: `python3 --version`
- Verificar logs: `tail -f /var/log/apache2/error.log`

### Histórico Vazio
- Verificar arquivos: `ls -la database/ status/ results/`
- O histórico é criado automaticamente ao fazer consultas

## 📄 Licença

Este projeto é de uso interno.

## 👤 Autor

Desenvolvido para consulta de números telefônicos em lote.
