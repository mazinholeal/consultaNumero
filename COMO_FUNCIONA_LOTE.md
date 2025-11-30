# 📚 Como Funciona a Consulta em Lote - Explicação Simples

## 🎯 Resumo Rápido

Imagine que você tem **1000 números** para consultar. O sistema não envia todos de uma vez (isso sobrecarregaria a API). Em vez disso, ele:

1. **Divide em grupos de 50 números** (chamados de "lotes")
2. **Envia até 3 lotes ao mesmo tempo** (em paralelo)
3. **Espera 0,5 segundos** entre cada envio
4. **Se der erro, tenta novamente até 3 vezes**
5. **Salva os resultados conforme vai recebendo**

---

## 📊 Passo a Passo Detalhado

### 1️⃣ **Leitura do Arquivo**

Quando você faz upload de um arquivo com números:
- O sistema lê todos os números do arquivo
- Remove espaços e caracteres inválidos
- Valida que cada número tem pelo menos 10 dígitos

**Exemplo:**
```
Arquivo com 1000 números
↓
Sistema lê e valida
↓
1000 números válidos prontos para processar
```

---

### 2️⃣ **Divisão em Lotes**

O sistema divide os números em grupos de **50 números** cada.

**Configuração:** `BATCH_SIZE = 50`

**Exemplo com 1000 números:**
```
Números: [1, 2, 3, ..., 1000]
↓
Lote 1: números 1-50    (50 números)
Lote 2: números 51-100 (50 números)
Lote 3: números 101-150 (50 números)
...
Lote 20: números 951-1000 (50 números)
```

**Total:** 20 lotes de 50 números cada

---

### 3️⃣ **Envio Paralelo (Concorrência)**

O sistema pode enviar **até 3 lotes ao mesmo tempo** (em paralelo).

**Configuração:** `MAX_CONCURRENT_REQUESTS = 3`

**Como funciona:**

```
Tempo 0s:
  → Lote 1 sendo enviado (50 números)
  → Lote 2 sendo enviado (50 números)  
  → Lote 3 sendo enviado (50 números)
  
Tempo 0.5s:
  → Lote 1 terminou ✓
  → Lote 2 terminou ✓
  → Lote 3 terminou ✓
  → Lote 4 sendo enviado agora
  → Lote 5 sendo enviado agora
  → Lote 6 sendo enviado agora
  
E assim por diante...
```

**Por que fazer isso?**
- ✅ Processa mais rápido (3 lotes ao mesmo tempo)
- ✅ Não sobrecarrega a API (máximo 3 requisições simultâneas)
- ✅ Mais eficiente que enviar um por vez

---

### 4️⃣ **Delay Entre Requisições**

Após cada lote ser processado, o sistema espera **0,5 segundos** antes de enviar o próximo.

**Configuração:** `REQUEST_DELAY = 0.5` segundos

**Por que esperar?**
- 🛡️ Protege a API de sobrecarga
- 🛡️ Evita ser bloqueado por fazer muitas requisições muito rápido
- 🛡️ Dá tempo para a API processar cada requisição

**Exemplo:**
```
Lote 1 enviado → espera 0.5s → Lote 2 enviado → espera 0.5s → Lote 3 enviado
```

---

### 5️⃣ **Tratamento de Erros (Retry)**

Se um lote der erro, o sistema tenta novamente **até 3 vezes**.

**Configuração:** `MAX_RETRIES = 3`

**Como funciona:**

```
Lote 5 enviado → ERRO!
↓
Espera 0.5s → Tenta novamente (tentativa 2)
↓
Ainda erro? → Espera 1.0s → Tenta novamente (tentativa 3)
↓
Ainda erro? → Marca como erro e continua com próximo lote
```

**Tipos de erro tratados:**
- ❌ Erro de conexão (internet caiu)
- ❌ Erro HTTP (servidor da API indisponível)
- ❌ Resposta inválida da API
- ❌ Timeout (API demorou muito para responder)

**Importante:** Mesmo se um lote der erro, o sistema **continua processando os outros lotes**.

---

### 6️⃣ **Salvamento Incremental**

Os resultados são salvos **conforme vão chegando**, não apenas no final.

**Por que isso é importante?**
- ✅ Se o sistema travar, você não perde tudo
- ✅ Pode acompanhar o progresso em tempo real
- ✅ Se precisar parar, pode retomar de onde parou

**Como funciona:**

```
Lote 1 processado → Salva resultados no arquivo
Lote 2 processado → Adiciona resultados ao arquivo
Lote 3 processado → Adiciona resultados ao arquivo
...
```

**Arquivos salvos:**
- `results/{job_id}.json` - Todos os resultados
- `status/{job_id}.json` - Status e progresso
- `status/{job_id}_checkpoint.json` - Checkpoint para retomar
- `status/{job_id}_errors.json` - Detalhes dos erros

---

### 7️⃣ **Checkpoint (Ponto de Controle)**

A cada **10 lotes processados**, o sistema salva um checkpoint.

**O que é checkpoint?**
É um "ponto de salvamento" que permite retomar o processamento se algo der errado.

**Exemplo:**
```
Lote 1-10 processados → Salva checkpoint
Lote 11-20 processados → Salva checkpoint
...
```

**Se o sistema travar:**
- Ao reiniciar, ele lê o checkpoint
- Descobre quais números já foram processados
- **Pula esses números** e continua de onde parou
- ✅ Não processa o mesmo número duas vezes!

---

## 📈 Exemplo Prático Completo

Vamos imaginar que você tem **250 números** para consultar:

### Passo 1: Divisão
```
250 números ÷ 50 = 5 lotes
```

### Passo 2: Processamento Paralelo

```
Tempo 0.0s:
  ├─ Lote 1 (números 1-50) → Enviando...
  ├─ Lote 2 (números 51-100) → Enviando...
  └─ Lote 3 (números 101-150) → Enviando...

Tempo 0.5s:
  ├─ Lote 1 → ✅ Concluído! (salvou resultados)
  ├─ Lote 2 → ✅ Concluído! (salvou resultados)
  ├─ Lote 3 → ✅ Concluído! (salvou resultados)
  ├─ Lote 4 (números 151-200) → Enviando...
  └─ Lote 5 (números 201-250) → Enviando...

Tempo 1.0s:
  ├─ Lote 4 → ✅ Concluído!
  └─ Lote 5 → ✅ Concluído!

✅ Processamento completo!
```

**Tempo total:** ~1 segundo (se tudo der certo)

---

## ⚙️ Configurações (Ajustáveis)

Você pode ajustar esses valores no arquivo `process_batch.py`:

| Configuração | Valor Padrão | O que faz |
|-------------|-------------|-----------|
| `BATCH_SIZE` | 50 | Quantos números por lote |
| `MAX_CONCURRENT_REQUESTS` | 3 | Quantos lotes simultâneos |
| `REQUEST_DELAY` | 0.5s | Tempo de espera entre lotes |
| `MAX_RETRIES` | 3 | Tentativas em caso de erro |

**Dicas:**
- ⬆️ Aumentar `BATCH_SIZE` → Mais rápido, mas pode sobrecarregar API
- ⬆️ Aumentar `MAX_CONCURRENT_REQUESTS` → Mais rápido, mas mais risco
- ⬆️ Diminuir `REQUEST_DELAY` → Mais rápido, mas pode ser bloqueado
- ⬆️ Aumentar `MAX_RETRIES` → Mais resiliente a erros temporários

---

## 🎯 Resumo Final

**O sistema funciona assim:**

1. 📁 Lê o arquivo → Valida números
2. ✂️ Divide em lotes de 50 números
3. 🚀 Envia até 3 lotes ao mesmo tempo
4. ⏱️ Espera 0.5s entre cada envio
5. 🔄 Se der erro, tenta até 3 vezes
6. 💾 Salva resultados conforme vai recebendo
7. 📌 Salva checkpoint a cada 10 lotes
8. ✅ Continua até processar todos os números

**Resultado:** Processamento rápido, seguro e confiável! 🎉

