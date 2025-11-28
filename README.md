# Sistema de Consulta de Números via API

Sistema completo para consulta de números telefônicos via API, com duas modalidades:
- **Consulta Individual**: Para consultar poucos números diretamente na interface
- **Consulta em Lote**: Para processar arquivos grandes com muitos números

Interface PHP com backend Python para processamento assíncrono.

## Estrutura do Projeto

```
consultanumero/
├── index.php          # Interface principal com abas (Individual e Lote)
├── consult.php        # Endpoint para consulta individual (PHP)
├── upload.php         # Processa upload e inicia processamento Python
├── status.php         # Endpoint AJAX para verificar status
├── results.php        # Página para visualizar resultados
├── download.php       # Download de resultados em JSON ou CSV
├── process_batch.py   # Script Python para processar lotes
├── requirements.txt   # Dependências Python (nenhuma externa necessária)
├── .htaccess         # Configurações Apache
├── uploads/          # Arquivos enviados pelos usuários
├── results/          # Resultados JSON das consultas
└── status/           # Arquivos de status dos jobs
```

## Requisitos

- PHP 7.4 ou superior (com extensão cURL)
- Python 3.6 ou superior
- Apache com mod_rewrite habilitado
- Permissões de escrita nos diretórios: uploads/, results/, status/

## Instalação

1. **Configurar permissões:**
```bash
chmod 755 uploads results status
chmod +x process_batch.py
```

2. **Configurar Apache:**
   - Certifique-se de que o mod_rewrite está habilitado
   - O arquivo `.htaccess` já está configurado

3. **Testar Python:**
```bash
python3 --version
```

## Configuração

### Parâmetros da Consulta Individual (consult.php)

Você pode ajustar os seguintes parâmetros no início do arquivo:

- `$MAX_NUMBERS = 100`: Limite de números por consulta individual
- `$BATCH_SIZE = 50`: Números por requisição à API

### Parâmetros do Script Python (process_batch.py)

Você pode ajustar os seguintes parâmetros no início do arquivo:

- `BATCH_SIZE = 50`: Números por requisição
- `MAX_CONCURRENT_REQUESTS = 3`: Requisições simultâneas
- `REQUEST_DELAY = 0.5`: Delay entre requisições (segundos)
- `MAX_RETRIES = 3`: Tentativas em caso de erro

### Limites PHP (.htaccess)

- `upload_max_filesize = 10M`: Tamanho máximo de upload
- `post_max_size = 10M`: Tamanho máximo de POST
- `max_execution_time = 300`: Tempo máximo de execução

## Uso

### Consulta Individual

1. Acesse `index.php` no navegador (primeira aba)
2. Digite os números separados por vírgula ou um por linha
3. Clique em "Consultar Números"
4. Os resultados aparecem imediatamente na tabela abaixo

**Limite:** Até 100 números por consulta individual

### Consulta em Lote

1. Acesse `index.php` e vá para a aba "Consulta em Lote"
2. Faça upload de um arquivo CSV ou TXT com números
3. Aguarde o processamento (acompanhe o progresso na tela)
4. Visualize os resultados ou faça download

## Formato do Arquivo

O arquivo pode ter números em dois formatos:

**Formato 1 - Separado por vírgula:**
```
11941900123,81981562716,11987654321
```

**Formato 2 - Um por linha:**
```
11941900123
81981562716
11987654321
```

## API Utilizada

- **URL:** `https://painel.tridtelecom.com.br/_7port/consulta.php`
- **Método:** GET
- **Parâmetro:** `numero` (números separados por vírgula)
- **Resposta:** JSON array com informações dos números

## Funcionalidades

### Consulta Individual
- ✅ Interface simples para consulta rápida
- ✅ Suporte a múltiplos números (até 100)
- ✅ Resultados instantâneos na mesma página
- ✅ Validação automática de números
- ✅ Processamento em lotes automático (50 números por requisição)

### Consulta em Lote
- ✅ Upload de arquivos CSV/TXT
- ✅ Validação de formato e tamanho
- ✅ Processamento assíncrono em background
- ✅ Acompanhamento de progresso em tempo real
- ✅ Controle de taxa e concorrência de requisições
- ✅ Tratamento de erros e retry automático
- ✅ Visualização de resultados em tabela
- ✅ Busca/filtro nos resultados
- ✅ Download em JSON ou CSV

### Interface
- ✅ Sistema de abas intuitivo
- ✅ Interface moderna e responsiva
- ✅ Drag & drop para upload de arquivos

## Segurança

- Validação de extensões de arquivo
- Limite de tamanho de upload
- Proteção de diretórios sensíveis via .htaccess
- Sanitização de inputs
- Escape de outputs HTML

## Troubleshooting

### Erro: "Permission denied" ao executar Python
```bash
chmod +x process_batch.py
```

### Erro: "Cannot write to directory"
```bash
chown -R www-data:www-data uploads results status
chmod 755 uploads results status
```

### Processamento não inicia
- Verifique se o Python está no PATH do Apache
- Verifique os logs do Apache: `tail -f /var/log/apache2/error.log`
- Teste o script Python manualmente:
```bash
python3 process_batch.py /caminho/arquivo.txt job_test_123
```

### Requisições muito lentas
- Ajuste `REQUEST_DELAY` no `process_batch.py`
- Reduza `MAX_CONCURRENT_REQUESTS` se necessário
- Aumente `BATCH_SIZE` para processar mais números por requisição

## Licença

Este projeto é fornecido como está, sem garantias.

