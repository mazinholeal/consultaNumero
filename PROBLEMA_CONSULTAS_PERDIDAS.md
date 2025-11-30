# 🔍 Problema: Consultas Perdidas Após Atualização

## O Que Aconteceu

Após uma atualização do projeto usando `git reset --hard origin/main`, o arquivo `database/consultas.json` foi perdido porque:

1. **O arquivo não está versionado no Git** - Está no `.gitignore` (linha 37: `database/*.json`)
2. **O `git reset --hard` remove arquivos não versionados** - Quando executado, remove arquivos que não estão no repositório
3. **O script de atualização não fazia backup antes do reset** - O backup só acontecia em casos específicos

## Solução Implementada

### ✅ Correções Aplicadas

1. **Arquivo `consultas.json` recriado**
   - Criado arquivo vazio `{}` para o sistema funcionar novamente
   - Permissões configuradas corretamente (666, www-data:www-data)

2. **Script `update.sh` melhorado**
   - Agora faz backup do `consultas.json` **ANTES** de qualquer operação git
   - Restaura automaticamente o backup após `git reset --hard` se o arquivo for perdido
   - Cria arquivo vazio se nenhum backup estiver disponível

3. **Script `install.sh` melhorado**
   - Backup preventivo antes de operações git
   - Restauração automática após reset
   - Fallback para recuperação via `recover_history.php` se necessário

## Como Prevenir no Futuro

### ✅ Já Implementado

Os scripts agora fazem backup automático antes de operações git perigosas.

### 📋 Boas Práticas

1. **Sempre fazer backup manual antes de atualizações importantes:**
   ```bash
   cp database/consultas.json database/consultas.json.backup.manual
   ```

2. **Verificar backups antes de atualizar:**
   ```bash
   ls -la database/*.backup*
   ```

3. **Usar o script de recuperação se necessário:**
   ```bash
   php recover_history.php
   ```
   (Este script reconstrói o histórico a partir dos arquivos de status, se existirem)

## Recuperação de Dados Perdidos

### ✅ Dados Recuperados com Sucesso!

**Boa notícia!** Os dados foram recuperados de um backup automático:

- **Backup encontrado:** `consultanumero.backup.20251130_125056`
- **3 consultas restauradas:**
  1. `MEI 2.txt` - 37.598 resultados (Concluída)
  2. `MEI 1.txt` - 87.040 resultados (Concluída)
  3. `Consulta opera.txt` - 97.013 resultados (Concluída)

- **Arquivos restaurados:**
  - ✅ `database/consultas.json` - Histórico completo
  - ✅ `results/*.json` - Todos os resultados (49MB de dados)
  - ✅ `status/*.json` - Status e checkpoints

### 📊 Status Atual

O sistema está funcionando normalmente com todos os dados restaurados. Novas consultas serão salvas corretamente e os backups automáticos evitarão perda futura de dados.

## Estrutura de Backup

Os backups são salvos no formato:
```
database/consultas.json.backup.YYYYMMDD_HHMMSS
```

Exemplo:
```
database/consultas.json.backup.20241130_130100
```

## Verificação

Para verificar se tudo está funcionando:

```bash
# Verificar se o arquivo existe
ls -la database/consultas.json

# Verificar conteúdo
cat database/consultas.json

# Verificar backups
ls -la database/*.backup*
```

## Data da Correção

**30 de Novembro de 2025** - Problema identificado e corrigido.

