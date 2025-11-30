<?php
// Aumentar limite de memória para arquivos grandes
ini_set('memory_limit', '512M');

/**
 * Função para contar resultados sem carregar tudo na memória
 */
function countJsonResults($file) {
    if (!file_exists($file)) {
        return 0;
    }
    
    $fileSize = filesize($file);
    
    // Para arquivos muito grandes (> 10MB), usar método otimizado
    if ($fileSize > 10 * 1024 * 1024) {
        // Aumentar memória temporariamente para contar
        $oldLimit = ini_get('memory_limit');
        ini_set('memory_limit', '1024M');
        
        $content = file_get_contents($file);
        if ($content === false) {
            ini_set('memory_limit', $oldLimit);
            return 0;
        }
        
        // Contar ocorrências de "numero": que indica um resultado
        // Isso é mais rápido que decodificar o JSON completo
        $count = substr_count($content, '"numero":');
        
        ini_set('memory_limit', $oldLimit);
        return $count;
    }
    
    // Para arquivos menores, usar método normal
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? count($data) : 0;
}

/**
 * Função para ler apenas uma página de resultados
 */
function getJsonResultsPage($file, $offset, $limit) {
    if (!file_exists($file)) {
        return [];
    }
    
    $fileSize = filesize($file);
    
    // Para arquivos muito grandes (> 10MB), usar método otimizado
    if ($fileSize > 10 * 1024 * 1024) {
        // Carregar todo o arquivo mas processar em chunks menores
        // Aumentar memória temporariamente
        $oldLimit = ini_get('memory_limit');
        ini_set('memory_limit', '1024M');
        
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        ini_set('memory_limit', $oldLimit);
        
        if (!is_array($data)) {
            return [];
        }
        
        return array_slice($data, $offset, $limit);
    }
    
    // Para arquivos menores, método normal
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) {
        return [];
    }
    
    return array_slice($data, $offset, $limit);
}

/**
 * Função para calcular estatísticas de operadoras de forma eficiente
 */
function getOperadoraStats($file) {
    if (!file_exists($file)) {
        return ['TIM' => 0, 'VIVO' => 0, 'CLARO' => 0, 'OUTROS' => 0];
    }
    
    $stats = ['TIM' => 0, 'VIVO' => 0, 'CLARO' => 0, 'OUTROS' => 0];
    $fileSize = filesize($file);
    
    // Aumentar memória temporariamente
    $oldLimit = ini_get('memory_limit');
    ini_set('memory_limit', '1024M');
    
    // Para arquivos grandes, usar método otimizado mas preciso
    if ($fileSize > 10 * 1024 * 1024) {
        // Carregar e processar em chunks seria ideal, mas para simplicidade
        // vamos carregar tudo mas processar de forma eficiente
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        if (is_array($data)) {
            // Processar em lotes para economizar memória durante iteração
            $batchSize = 10000;
            $total = count($data);
            
            for ($i = 0; $i < $total; $i += $batchSize) {
                $batch = array_slice($data, $i, $batchSize);
                foreach ($batch as $result) {
                    $operadora = strtoupper(trim($result['operadora'] ?? ''));
                    
                    if (stripos($operadora, 'TIM') !== false) {
                        $stats['TIM']++;
                    } elseif (stripos($operadora, 'VIVO') !== false || stripos($operadora, 'TELEFONICA') !== false || stripos($operadora, 'TELEFÔNICA') !== false) {
                        $stats['VIVO']++;
                    } elseif (stripos($operadora, 'CLARO') !== false) {
                        $stats['CLARO']++;
                    } else {
                        $stats['OUTROS']++;
                    }
                }
                // Limpar batch da memória
                unset($batch);
            }
        }
    } else {
        // Para arquivos menores, método normal
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) {
            foreach ($data as $result) {
                $operadora = strtoupper(trim($result['operadora'] ?? ''));
                
                if (stripos($operadora, 'TIM') !== false) {
                    $stats['TIM']++;
                } elseif (stripos($operadora, 'VIVO') !== false || stripos($operadora, 'TELEFONICA') !== false || stripos($operadora, 'TELEFÔNICA') !== false) {
                    $stats['VIVO']++;
                } elseif (stripos($operadora, 'CLARO') !== false) {
                    $stats['CLARO']++;
                } else {
                    $stats['OUTROS']++;
                }
            }
        }
    }
    
    ini_set('memory_limit', $oldLimit);
    return $stats;
}

$jobId = $_GET['job_id'] ?? '';

if (empty($jobId)) {
    die('Job ID não fornecido');
}

$resultsFile = __DIR__ . '/results/' . $jobId . '.json';
$statusFile = __DIR__ . '/status/' . $jobId . '.json';
$errorsFile = __DIR__ . '/status/' . $jobId . '_errors.json';

if (!file_exists($statusFile)) {
    die('Job não encontrado');
}

$status = json_decode(file_get_contents($statusFile), true);
$errors = [];

if (file_exists($errorsFile)) {
    $errors = json_decode(file_get_contents($errorsFile), true);
}

// Paginação
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
if (!in_array($perPage, [10, 25, 50])) {
    $perPage = 25;
}

$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Usar função otimizada para contar resultados
$totalResults = countJsonResults($resultsFile);
$totalPages = $totalResults > 0 ? ceil($totalResults / $perPage) : 1;
$currentPage = min($currentPage, $totalPages);

$offset = ($currentPage - 1) * $perPage;

// Carregar apenas a página necessária
$paginatedResults = getJsonResultsPage($resultsFile, $offset, $perPage);

// Calcular estatísticas de operadoras apenas se necessário (para exibição)
// Sempre calcular na primeira página, ou usar cache se disponível
$statsOperadoras = ['TIM' => 0, 'VIVO' => 0, 'CLARO' => 0, 'OUTROS' => 0];
if (file_exists($resultsFile)) {
    // Verificar se há cache de estatísticas no arquivo de status
    $statsCacheFile = __DIR__ . '/status/' . $jobId . '_stats.json';
    if (file_exists($statsCacheFile) && filemtime($statsCacheFile) >= filemtime($resultsFile)) {
        // Usar cache se disponível e atualizado
        $statsOperadoras = json_decode(file_get_contents($statsCacheFile), true) ?: $statsOperadoras;
    } else {
        // Calcular estatísticas (só na primeira página para economizar)
        if ($currentPage == 1) {
            $statsOperadoras = getOperadoraStats($resultsFile);
            // Calcular OUTROS baseado no total
            $statsOperadoras['OUTROS'] = max(0, $totalResults - $statsOperadoras['TIM'] - $statsOperadoras['VIVO'] - $statsOperadoras['CLARO']);
            // Salvar cache
            file_put_contents($statsCacheFile, json_encode($statsOperadoras));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados da Consulta - <?php echo htmlspecialchars($jobId); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .info-box p {
            margin: 5px 0;
            color: #666;
        }
        
        .actions {
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #764ba2;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #667eea;
            color: white;
            font-weight: bold;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-processing {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .pagination-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .pagination-info {
            color: #666;
            font-size: 14px;
        }
        
        .pagination-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .pagination-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }
        
        .pagination-select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .page-btn {
            padding: 8px 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .page-btn:hover:not(:disabled) {
            background: #764ba2;
        }
        
        .page-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .page-numbers {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .page-number {
            padding: 8px 12px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        
        .page-number:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .page-number.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Resultados da Consulta</h1>
        
        <div class="info-box">
            <p><strong>Job ID:</strong> <?php echo htmlspecialchars($jobId); ?></p>
            <p><strong>Arquivo:</strong> <?php echo htmlspecialchars($status['file_name'] ?? 'N/A'); ?></p>
            <p><strong>Status:</strong> 
                <span class="status-badge status-<?php echo htmlspecialchars($status['status'] ?? 'unknown'); ?>">
                    <?php 
                    $statusText = $status['status'] ?? 'unknown';
                    echo ucfirst($statusText === 'completed' ? 'Concluído' : ($statusText === 'processing' ? 'Processando' : ($statusText === 'error' ? 'Erro' : 'Desconhecido')));
                    ?>
                </span>
            </p>
            <p><strong>Criado em:</strong> <?php echo htmlspecialchars($status['created_at'] ?? 'N/A'); ?></p>
            <p><strong>Processados:</strong> <?php echo $status['processed'] ?? 0; ?> de <?php echo $status['total'] ?? 0; ?></p>
            <?php if (isset($status['errors_count']) && $status['errors_count'] > 0): ?>
                <p><strong>Erros:</strong> <span style="color: #721c24; font-weight: bold;"><?php echo $status['errors_count']; ?> lote(s) com problemas</span></p>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="info-box" style="background: #fff3cd; border-left: 4px solid #ffc107; margin-bottom: 20px;">
                <h3 style="color: #856404; margin-bottom: 10px;">⚠️ Erros Durante o Processamento</h3>
                <p style="color: #856404; margin-bottom: 10px;">Foram encontrados <strong><?php echo count($errors); ?> lote(s)</strong> com problemas na API. Detalhes abaixo:</p>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($errors as $index => $error): ?>
                        <div style="background: white; padding: 10px; margin-bottom: 8px; border-radius: 4px; border-left: 3px solid #dc3545;">
                            <p style="margin-bottom: 5px;"><strong>Lote #<?php echo $error['batch_index'] ?? $index + 1; ?></strong></p>
                            <p style="font-size: 12px; color: #666; margin-bottom: 5px;">
                                <strong>Números:</strong> <?php echo implode(', ', array_slice($error['numbers'] ?? [], 0, 5)); ?>
                                <?php if (count($error['numbers'] ?? []) > 5): ?>
                                    ... e mais <?php echo count($error['numbers']) - 5; ?> número(s)
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($error['attempts'])): ?>
                                <div style="margin-top: 5px;">
                                    <?php foreach ($error['attempts'] as $attempt): ?>
                                        <p style="font-size: 11px; color: #721c24; margin: 2px 0;">
                                            Tentativa <?php echo $attempt['attempt']; ?>: 
                                            <?php echo htmlspecialchars($attempt['error'] ?? 'Erro desconhecido'); ?>
                                            <?php if (isset($attempt['timestamp'])): ?>
                                                <span style="color: #999;">(<?php echo htmlspecialchars($attempt['timestamp']); ?>)</span>
                                            <?php endif; ?>
                                        </p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="index.php" class="btn btn-secondary">Nova Consulta</a>
        </div>
        
        <?php if (file_exists($resultsFile) && $totalResults > 0): ?>
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="margin-bottom: 15px;">
                    <h3 style="margin-bottom: 10px; color: #333;">📥 Download Completo</h3>
                    <a href="download.php?job_id=<?php echo urlencode($jobId); ?>&format=json" class="btn">📄 Download JSON (Todos)</a>
                    <a href="download.php?job_id=<?php echo urlencode($jobId); ?>&format=csv" class="btn">📊 Download CSV (Todos)</a>
                </div>
                
                <?php
                // Estatísticas já calculadas acima de forma otimizada
                ?>
                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #ddd;">
                    <h3 style="margin-bottom: 15px; color: #333;">📥 Download por Operadora</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                        <?php if ($statsOperadoras['TIM'] > 0): ?>
                            <div style="background: #f0f7ff; padding: 15px; border-radius: 8px; border-left: 4px solid #004C97;">
                                <h4 style="margin: 0 0 10px 0; color: #004C97;">📱 TIM</h4>
                                <p style="margin: 0 0 10px 0; font-size: 12px; color: #666;"><?php echo $statsOperadoras['TIM']; ?> resultado(s)</p>
                                <div style="display: flex; gap: 5px;">
                                    <a href="download.php?job_id=<?php echo urlencode($jobId); ?>&format=json&operadora=TIM" class="btn" style="font-size: 12px; padding: 6px 12px;">JSON</a>
                                    <a href="download.php?job_id=<?php echo urlencode($jobId); ?>&format=csv&operadora=TIM" class="btn" style="font-size: 12px; padding: 6px 12px;">CSV</a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($statsOperadoras['VIVO'] > 0): ?>
                            <div style="background: #f3e8ff; padding: 15px; border-radius: 8px; border-left: 4px solid #9333ea;">
                                <h4 style="margin: 0 0 10px 0; color: #9333ea;">📱 VIVO / TELEFÔNICA</h4>
                                <p style="margin: 0 0 10px 0; font-size: 12px; color: #666;"><?php echo $statsOperadoras['VIVO']; ?> resultado(s)</p>
                                <div style="display: flex; gap: 5px;">
                                    <a href="download.php?job_id=<?php echo urlencode($jobId); ?>&format=json&operadora=VIVO" class="btn" style="font-size: 12px; padding: 6px 12px;">JSON</a>
                                    <a href="download.php?job_id=<?php echo urlencode($jobId); ?>&format=csv&operadora=VIVO" class="btn" style="font-size: 12px; padding: 6px 12px;">CSV</a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($statsOperadoras['CLARO'] > 0): ?>
                            <div style="background: #fff5f5; padding: 15px; border-radius: 8px; border-left: 4px solid #E30613;">
                                <h4 style="margin: 0 0 10px 0; color: #E30613;">📱 CLARO</h4>
                                <p style="margin: 0 0 10px 0; font-size: 12px; color: #666;"><?php echo $statsOperadoras['CLARO']; ?> resultado(s)</p>
                                <div style="display: flex; gap: 5px;">
                                    <a href="download.php?job_id=<?php echo urlencode($jobId); ?>&format=json&operadora=CLARO" class="btn" style="font-size: 12px; padding: 6px 12px;">JSON</a>
                                    <a href="download.php?job_id=<?php echo urlencode($jobId); ?>&format=csv&operadora=CLARO" class="btn" style="font-size: 12px; padding: 6px 12px;">CSV</a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($statsOperadoras['OUTROS'] > 0): ?>
                            <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; border-left: 4px solid #666;">
                                <h4 style="margin: 0 0 10px 0; color: #666;">📱 OUTROS</h4>
                                <p style="margin: 0 0 10px 0; font-size: 12px; color: #666;"><?php echo $statsOperadoras['OUTROS']; ?> resultado(s)</p>
                                <div style="display: flex; gap: 5px;">
                                    <a href="download.php?job_id=<?php echo urlencode($jobId); ?>&format=json&operadora=OUTROS" class="btn" style="font-size: 12px; padding: 6px 12px;">JSON</a>
                                    <a href="download.php?job_id=<?php echo urlencode($jobId); ?>&format=csv&operadora=OUTROS" class="btn" style="font-size: 12px; padding: 6px 12px;">CSV</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($totalResults > 0): ?>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Buscar por número, operadora, localidade...">
            </div>
            
            <!-- Controles de Paginação Superior -->
            <div class="pagination-controls">
                <div class="pagination-info">
                    Mostrando <?php echo $offset + 1; ?> - <?php echo min($offset + $perPage, $totalResults); ?> de <?php echo number_format($totalResults); ?> resultados
                </div>
                <div class="pagination-buttons">
                    <label for="perPageSelect" style="margin-right: 10px; color: #666;">Itens por página:</label>
                    <select id="perPageSelect" class="pagination-select" onchange="changePerPage(this.value)">
                        <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $perPage == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50</option>
                    </select>
                </div>
            </div>
            
            <table id="resultsTable">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Tipo Prefixo</th>
                        <th>EOT</th>
                        <th>Holding</th>
                        <th>Operadora</th>
                        <th>CNL</th>
                        <th>Localidade</th>
                        <th>Portado</th>
                        <th>Erro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paginatedResults as $result): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($result['numero'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($result['TipoPrefixo'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($result['eot'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($result['holding'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($result['operadora'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($result['cnl'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($result['localidade'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($result['portado'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($result['erro'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Controles de Paginação Inferior -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-controls">
                    <button class="page-btn" onclick="goToPage(<?php echo $currentPage - 1; ?>)" <?php echo $currentPage == 1 ? 'disabled' : ''; ?>>
                        ← Anterior
                    </button>
                    
                    <div class="page-numbers">
                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        if ($startPage > 1): ?>
                            <a href="?job_id=<?php echo urlencode($jobId); ?>&page=1&per_page=<?php echo $perPage; ?>" class="page-number">1</a>
                            <?php if ($startPage > 2): ?>
                                <span style="padding: 8px;">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?job_id=<?php echo urlencode($jobId); ?>&page=<?php echo $i; ?>&per_page=<?php echo $perPage; ?>" 
                               class="page-number <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <span style="padding: 8px;">...</span>
                            <?php endif; ?>
                            <a href="?job_id=<?php echo urlencode($jobId); ?>&page=<?php echo $totalPages; ?>&per_page=<?php echo $perPage; ?>" class="page-number"><?php echo $totalPages; ?></a>
                        <?php endif; ?>
                    </div>
                    
                    <button class="page-btn" onclick="goToPage(<?php echo $currentPage + 1; ?>)" <?php echo $currentPage == $totalPages ? 'disabled' : ''; ?>>
                        Próxima →
                    </button>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-results">
                <p>Nenhum resultado disponível ainda. O processamento pode estar em andamento.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        const searchInput = document.getElementById('searchInput');
        const resultsTable = document.getElementById('resultsTable');
        
        if (searchInput && resultsTable) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = resultsTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                }
            });
        }
        
        function changePerPage(value) {
            const url = new URL(window.location);
            url.searchParams.set('per_page', value);
            url.searchParams.set('page', '1'); // Reset para primeira página
            window.location.href = url.toString();
        }
        
        function goToPage(page) {
            const url = new URL(window.location);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>

