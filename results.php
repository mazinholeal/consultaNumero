<?php
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
$results = [];
$errors = [];

if (file_exists($resultsFile)) {
    $results = json_decode(file_get_contents($resultsFile), true);
}

if (file_exists($errorsFile)) {
    $errors = json_decode(file_get_contents($errorsFile), true);
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
            <?php if (file_exists($resultsFile)): ?>
                <a href="download.php?job_id=<?php echo urlencode($jobId); ?>&format=json" class="btn">Download JSON</a>
                <a href="download.php?job_id=<?php echo urlencode($jobId); ?>&format=csv" class="btn">Download CSV</a>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($results)): ?>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Buscar por número, operadora, localidade...">
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
                    <?php foreach ($results as $result): ?>
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
    </script>
</body>
</html>

