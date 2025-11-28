#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script Python para processar consultas em lote via API
Com suporte a salvamento incremental e retomada de processamento
"""

import sys
import os
import json
import time
import re
from urllib.parse import urlencode
from urllib.request import urlopen, Request
from urllib.error import URLError, HTTPError
import threading
from queue import Queue

# Configurações
API_URL = "https://painel.tridtelecom.com.br/_7port/consulta.php"
BATCH_SIZE = 50  # Números por requisição
MAX_CONCURRENT_REQUESTS = 3  # Máximo de requisições simultâneas
REQUEST_DELAY = 0.5  # Delay entre requisições em segundos
MAX_RETRIES = 3  # Tentativas em caso de erro

def read_numbers_from_file(file_path):
    """Lê números do arquivo CSV ou TXT"""
    numbers = []
    
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read().strip()
            
        # Remove BOM se presente
        if content.startswith('\ufeff'):
            content = content[1:]
        
        # Tenta separar por vírgula primeiro
        if ',' in content:
            numbers = [n.strip() for n in content.split(',') if n.strip()]
        else:
            # Separa por linha
            numbers = [n.strip() for n in content.split('\n') if n.strip()]
        
        # Remove caracteres não numéricos e valida
        cleaned_numbers = []
        for num in numbers:
            # Remove tudo exceto dígitos
            cleaned = re.sub(r'\D', '', num)
            if cleaned and len(cleaned) >= 10:  # Validação básica
                cleaned_numbers.append(cleaned)
        
        return cleaned_numbers
    
    except Exception as e:
        print(f"Erro ao ler arquivo: {e}", file=sys.stderr)
        return []

def load_checkpoint(checkpoint_file):
    """Carrega checkpoint de processamento anterior"""
    if os.path.exists(checkpoint_file):
        try:
            with open(checkpoint_file, 'r', encoding='utf-8') as f:
                checkpoint = json.load(f)
            return checkpoint.get('processed_numbers', set()), checkpoint.get('errors', [])
        except Exception as e:
            print(f"Erro ao carregar checkpoint: {e}", file=sys.stderr)
    return set(), []

def save_checkpoint(checkpoint_file, processed_numbers, errors):
    """Salva checkpoint de processamento"""
    try:
        checkpoint = {
            'processed_numbers': list(processed_numbers),
            'errors': errors,
            'last_update': time.strftime('%Y-%m-%d %H:%M:%S')
        }
        with open(checkpoint_file, 'w', encoding='utf-8') as f:
            json.dump(checkpoint, f, indent=2, ensure_ascii=False)
    except Exception as e:
        print(f"Erro ao salvar checkpoint: {e}", file=sys.stderr)

def save_results_incremental(results_file, new_results, lock):
    """Salva resultados incrementalmente"""
    try:
        lock.acquire()
        # Carrega resultados existentes
        existing_results = []
        if os.path.exists(results_file):
            try:
                with open(results_file, 'r', encoding='utf-8') as f:
                    existing_results = json.load(f)
            except:
                existing_results = []
        
        # Adiciona novos resultados
        existing_results.extend(new_results)
        
        # Salva de volta
        with open(results_file, 'w', encoding='utf-8') as f:
            json.dump(existing_results, f, indent=2, ensure_ascii=False)
    except Exception as e:
        print(f"Erro ao salvar resultados incrementais: {e}", file=sys.stderr)
    finally:
        lock.release()

def consult_api(numbers_batch, batch_index):
    """Consulta a API com um lote de números"""
    if not numbers_batch:
        return []
    
    # Concatena números separados por vírgula
    numbers_str = ','.join(numbers_batch)
    
    # Monta URL
    url = f"{API_URL}?numero={numbers_str}"
    
    error_details = {
        'batch_index': batch_index,
        'numbers': numbers_batch,
        'attempts': []
    }
    
    for attempt in range(MAX_RETRIES):
        try:
            req = Request(url)
            req.add_header('User-Agent', 'Mozilla/5.0 (compatible; BatchConsult/1.0)')
            
            start_time = time.time()
            with urlopen(req, timeout=30) as response:
                data = response.read().decode('utf-8')
                result = json.loads(data)
                
                if isinstance(result, list):
                    return result, None
                else:
                    error_msg = f"Resposta inesperada da API: {result}"
                    error_details['attempts'].append({
                        'attempt': attempt + 1,
                        'error': error_msg,
                        'timestamp': time.strftime('%Y-%m-%d %H:%M:%S')
                    })
                    if attempt == MAX_RETRIES - 1:
                        return [{"numero": num, "erro": "Resposta inválida da API"} 
                               for num in numbers_batch], error_details
                    time.sleep(REQUEST_DELAY * (attempt + 1))
        
        except HTTPError as e:
            error_msg = f"HTTP {e.code}: {e.reason}"
            error_details['attempts'].append({
                'attempt': attempt + 1,
                'error': error_msg,
                'http_code': e.code,
                'timestamp': time.strftime('%Y-%m-%d %H:%M:%S')
            })
            if attempt < MAX_RETRIES - 1:
                time.sleep(REQUEST_DELAY * (attempt + 1))
            else:
                return [{"numero": num, "erro": error_msg} 
                       for num in numbers_batch], error_details
        
        except URLError as e:
            error_msg = f"Erro de conexão: {e.reason}"
            error_details['attempts'].append({
                'attempt': attempt + 1,
                'error': error_msg,
                'timestamp': time.strftime('%Y-%m-%d %H:%M:%S')
            })
            if attempt < MAX_RETRIES - 1:
                time.sleep(REQUEST_DELAY * (attempt + 1))
            else:
                return [{"numero": num, "erro": error_msg} 
                       for num in numbers_batch], error_details
        
        except json.JSONDecodeError as e:
            error_msg = f"Erro ao decodificar JSON: {str(e)}"
            error_details['attempts'].append({
                'attempt': attempt + 1,
                'error': error_msg,
                'timestamp': time.strftime('%Y-%m-%d %H:%M:%S')
            })
            return [{"numero": num, "erro": "Resposta inválida da API"} 
                   for num in numbers_batch], error_details
        
        except Exception as e:
            error_msg = f"Erro inesperado: {str(e)}"
            error_details['attempts'].append({
                'attempt': attempt + 1,
                'error': error_msg,
                'timestamp': time.strftime('%Y-%m-%d %H:%M:%S')
            })
            if attempt < MAX_RETRIES - 1:
                time.sleep(REQUEST_DELAY * (attempt + 1))
            else:
                return [{"numero": num, "erro": error_msg} for num in numbers_batch], error_details
    
    return [], error_details

def worker(queue, results_file, status_file, job_id, checkpoint_file, processed_set, errors_list, lock):
    """Worker thread para processar requisições"""
    while True:
        batch = queue.get()
        if batch is None:
            break
        
        batch_numbers = batch['numbers']
        batch_index = batch['index']
        
        # Verifica se já foi processado (retomada)
        if all(num in processed_set for num in batch_numbers):
            queue.task_done()
            continue
        
        # Consulta API
        batch_results, error_details = consult_api(batch_numbers, batch_index)
        
        # Se houve erro detalhado, adiciona à lista
        if error_details and error_details['attempts']:
            errors_list.append(error_details)
            save_checkpoint(checkpoint_file, processed_set, errors_list)
        
        # Adiciona números processados ao conjunto
        for num in batch_numbers:
            processed_set.add(num)
        
        # Salva resultados incrementalmente
        if batch_results:
            save_results_incremental(results_file, batch_results, lock)
        
        # Atualiza status
        update_status(status_file, job_id, len(processed_set), batch['total'], errors_list)
        
        # Salva checkpoint periodicamente
        if batch_index % 10 == 0:
            save_checkpoint(checkpoint_file, processed_set, errors_list)
        
        # Delay para evitar sobrecarga
        time.sleep(REQUEST_DELAY)
        
        queue.task_done()

def update_status(status_file, job_id, processed, total, errors_list):
    """Atualiza arquivo de status"""
    try:
        if os.path.exists(status_file):
            with open(status_file, 'r', encoding='utf-8') as f:
                status = json.load(f)
        else:
            status = {}
        
        status['status'] = 'processing'
        status['processed'] = processed
        status['total'] = total
        status['progress'] = int((processed / total * 100)) if total > 0 else 0
        status['updated_at'] = time.strftime('%Y-%m-%d %H:%M:%S')
        status['errors_count'] = len(errors_list)
        
        # Adiciona últimos erros ao status (últimos 5)
        if errors_list:
            status['recent_errors'] = errors_list[-5:]
        
        with open(status_file, 'w', encoding='utf-8') as f:
            json.dump(status, f, indent=2, ensure_ascii=False)
    
    except Exception as e:
        print(f"Erro ao atualizar status: {e}", file=sys.stderr)

def main():
    if len(sys.argv) < 3:
        print("Uso: python3 process_batch.py <caminho_arquivo> <job_id>", file=sys.stderr)
        sys.exit(1)
    
    file_path = sys.argv[1]
    job_id = sys.argv[2]
    
    # Caminhos
    base_dir = os.path.dirname(os.path.abspath(__file__))
    status_file = os.path.join(base_dir, 'status', f'{job_id}.json')
    results_file = os.path.join(base_dir, 'results', f'{job_id}.json')
    checkpoint_file = os.path.join(base_dir, 'status', f'{job_id}_checkpoint.json')
    errors_file = os.path.join(base_dir, 'status', f'{job_id}_errors.json')
    results_dir = os.path.join(base_dir, 'results')
    
    # Criar diretório de resultados se não existir
    if not os.path.exists(results_dir):
        os.makedirs(results_dir, mode=0o755)
    
    # Inicializa status
    try:
        with open(status_file, 'r', encoding='utf-8') as f:
            status = json.load(f)
        status['status'] = 'processing'
        status['message'] = 'Processando números...'
        with open(status_file, 'w', encoding='utf-8') as f:
            json.dump(status, f, indent=2, ensure_ascii=False)
    except Exception as e:
        print(f"Erro ao inicializar status: {e}", file=sys.stderr)
        sys.exit(1)
    
    # Lê números do arquivo
    numbers = read_numbers_from_file(file_path)
    
    if not numbers:
        status['status'] = 'error'
        status['message'] = 'Nenhum número válido encontrado no arquivo'
        with open(status_file, 'w', encoding='utf-8') as f:
            json.dump(status, f, indent=2, ensure_ascii=False)
        sys.exit(1)
    
    total_numbers = len(numbers)
    
    # Carrega checkpoint se existir (retomada)
    processed_set, errors_list = load_checkpoint(checkpoint_file)
    if processed_set:
        print(f"Retomando processamento: {len(processed_set)} números já processados", file=sys.stderr)
    
    # Filtra números já processados
    numbers_to_process = [n for n in numbers if n not in processed_set]
    
    if not numbers_to_process:
        print("Todos os números já foram processados!", file=sys.stderr)
        status['status'] = 'completed'
        status['processed'] = len(processed_set)
        status['total'] = total_numbers
        status['progress'] = 100
        status['completed_at'] = time.strftime('%Y-%m-%d %H:%M:%S')
        status['message'] = f'Processamento concluído. {len(processed_set)} resultados obtidos.'
        with open(status_file, 'w', encoding='utf-8') as f:
            json.dump(status, f, indent=2, ensure_ascii=False)
        sys.exit(0)
    
    # Divide em lotes apenas números não processados
    batches = []
    for i in range(0, len(numbers_to_process), BATCH_SIZE):
        batch_numbers = numbers_to_process[i:i + BATCH_SIZE]
        batches.append({
            'numbers': batch_numbers,
            'index': (len(processed_set) + i) // BATCH_SIZE,
            'total': total_numbers
        })
    
    # Cria fila e workers
    queue = Queue()
    threads = []
    lock = threading.Lock()
    
    # Inicia workers
    for _ in range(MAX_CONCURRENT_REQUESTS):
        t = threading.Thread(target=worker, args=(queue, results_file, status_file, job_id, checkpoint_file, processed_set, errors_list, lock))
        t.start()
        threads.append(t)
    
    # Adiciona batches na fila
    for batch in batches:
        queue.put(batch)
    
    # Aguarda conclusão
    queue.join()
    
    # Para workers
    for _ in range(MAX_CONCURRENT_REQUESTS):
        queue.put(None)
    
    for t in threads:
        t.join()
    
    # Salva erros finais
    if errors_list:
        try:
            with open(errors_file, 'w', encoding='utf-8') as f:
                json.dump(errors_list, f, indent=2, ensure_ascii=False)
        except Exception as e:
            print(f"Erro ao salvar arquivo de erros: {e}", file=sys.stderr)
    
    # Salva checkpoint final
    save_checkpoint(checkpoint_file, processed_set, errors_list)
    
    # Atualiza status final
    status['status'] = 'completed'
    status['processed'] = len(processed_set)
    status['total'] = total_numbers
    status['progress'] = 100
    status['completed_at'] = time.strftime('%Y-%m-%d %H:%M:%S')
    status['errors_count'] = len(errors_list)
    status['message'] = f'Processamento concluído. {len(processed_set)} resultados obtidos.'
    
    if errors_list:
        status['message'] += f' {len(errors_list)} lote(s) com erros.'
    
    with open(status_file, 'w', encoding='utf-8') as f:
        json.dump(status, f, indent=2, ensure_ascii=False)
    
    print(f"Processamento concluído: {len(processed_set)} resultados salvos em {results_file}")
    if errors_list:
        print(f"Atenção: {len(errors_list)} lote(s) tiveram erros. Verifique {errors_file}", file=sys.stderr)

if __name__ == '__main__':
    main()
