<?php
/**
 * Script de migração via web
 * Acesse: http://seu-servidor/consultanumero/migrate_web.php
 */

header('Content-Type: text/html; charset=utf-8');

// Verificar se é POST (execução) ou GET (página inicial)
$execute = isset($_POST['execute']) && $_POST['execute'] === '1';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migração de Resultados</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 { color: #333; margin-top: 0; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: #004C97; }
        pre {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border-left: 4px solid #004C97;
            font-size: 13px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #004C97;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
        }
        .btn:hover { background: #003366; }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-box {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #004C97;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>🔄 Migração de Resultados Antigos</h1>
        
        <?php
        // Verificar módulo SQLite
        if (!extension_loaded('pdo_sqlite')) {
            echo "<div class='error'>❌ Erro: Módulo PHP SQLite não está instalado!</div>";
            echo "<p>Execute no servidor via SSH:</p>";
            echo "<pre>apt-get install -y php-sqlite3\nsystemctl restart apache2</pre>";
            echo "<p><a href='check_migration.php' class='btn'>Verificar Status</a></p>";
            exit;
        }
        
        if ($execute) {
            // Executar migração
            echo "<h2>Executando Migração...</h2>";
            echo "<pre>";
            
            try {
                require_once __DIR__ . '/database.php';
                
                $db = new ConsultaDatabase();
                $statusDir = __DIR__ . '/status/';
                $resultsDir = __DIR__ . '/results/';
                $uploadsDir = __DIR__ . '/uploads/';
                
                if (!is_dir($statusDir)) {
                    echo "❌ Diretório de status não encontrado!\n";
                    exit;
                }
                
                // Buscar todos os arquivos de status
                $statusFiles = glob($statusDir . '*.json');
                $statusFiles = array_filter($statusFiles, function($file) {
                    return !preg_match('/_(checkpoint|errors)\.json$/', $file);
                });
                
                $imported = 0;
                $skipped = 0;
                $errors = 0;
                
                echo "Encontrados " . count($statusFiles) . " arquivos de status\n\n";
                
                foreach ($statusFiles as $statusFile) {
                    $statusData = json_decode(file_get_contents($statusFile), true);
                    
                    if (!$statusData || !isset($statusData['job_id'])) {
                        echo "⚠️  Arquivo inválido: " . basename($statusFile) . "\n";
                        $errors++;
                        continue;
                    }
                    
                    $jobId = $statusData['job_id'];
                    
                    // Verificar se já existe no banco
                    $existing = $db->getConsulta($jobId);
                    if ($existing) {
                        echo "⏭️  Já existe: $jobId\n";
                        $skipped++;
                        continue;
                    }
                    
                    // Buscar arquivo de upload relacionado
                    $filePath = null;
                    $fileName = $statusData['file_name'] ?? 'arquivo_desconhecido.txt';
                    
                    if (isset($statusData['file_path']) && file_exists($statusData['file_path'])) {
                        $filePath = $statusData['file_path'];
                    } else {
                        // Tentar encontrar pelo padrão de nome
                        $uploadFiles = glob($uploadsDir . $jobId . '_*');
                        if (!empty($uploadFiles)) {
                            $filePath = $uploadFiles[0];
                            $fileName = basename($filePath);
                            $fileName = preg_replace('/^' . preg_quote($jobId, '/') . '_/', '', $fileName);
                        }
                    }
                    
                    // Contar resultados se existir
                    $totalResults = 0;
                    $resultsFile = $resultsDir . $jobId . '.json';
                    if (file_exists($resultsFile)) {
                        $results = json_decode(file_get_contents($resultsFile), true);
                        if (is_array($results)) {
                            $totalResults = count($results);
                        }
                    }
                    
                    // Criar registro no banco
                    try {
                        $db->createConsulta($jobId, $fileName, $filePath);
                        
                        // Atualizar com dados do status
                        $db->updateConsulta($jobId, [
                            'status' => $statusData['status'] ?? 'unknown',
                            'total' => $statusData['total'] ?? $totalResults,
                            'processed' => $statusData['processed'] ?? $totalResults,
                            'progress' => $statusData['progress'] ?? ($totalResults > 0 ? 100 : 0),
                            'errors_count' => $statusData['errors_count'] ?? 0,
                            'message' => $statusData['message'] ?? ''
                        ]);
                        
                        // Se estiver completo, atualizar completed_at
                        if (($statusData['status'] ?? '') === 'completed') {
                            $db->updateConsulta($jobId, [
                                'status' => 'completed'
                            ]);
                        }
                        
                        echo "✅ Importado: $jobId - {$statusData['file_name']} ({$statusData['processed']}/{$statusData['total']})\n";
                        $imported++;
                    } catch (Exception $e) {
                        echo "❌ Erro ao importar $jobId: " . $e->getMessage() . "\n";
                        $errors++;
                    }
                }
                
                echo "\n========================================\n";
                echo "Migração Concluída!\n";
                echo "========================================\n";
                echo "✅ Importados: $imported\n";
                echo "⏭️  Ignorados (já existentes): $skipped\n";
                echo "❌ Erros: $errors\n";
                
            } catch (Exception $e) {
                echo "❌ Erro fatal: " . $e->getMessage() . "\n";
            }
            
            echo "</pre>";
            
            echo "<div class='stats'>";
            echo "<div class='stat-box'><div class='stat-number'>$imported</div><div class='stat-label'>Importados</div></div>";
            echo "<div class='stat-box'><div class='stat-number'>$skipped</div><div class='stat-label'>Ignorados</div></div>";
            echo "<div class='stat-box'><div class='stat-number'>$errors</div><div class='stat-label'>Erros</div></div>";
            echo "</div>";
            
            echo "<p><a href='historico.php' class='btn'>Ver Histórico</a> <a href='check_migration.php' class='btn'>Verificar Status</a></p>";
            
        } else {
            // Mostrar informações antes de executar
            echo "<h2>Informações</h2>";
            echo "<p>Este script irá importar todos os resultados antigos (arquivos JSON) para o banco de dados SQLite.</p>";
            
            $statusDir = __DIR__ . '/status/';
            $statusFiles = [];
            if (is_dir($statusDir)) {
                $files = glob($statusDir . '*.json');
                $statusFiles = array_filter($files, function($file) {
                    return !preg_match('/_(checkpoint|errors)\.json$/', $file);
                });
            }
            
            echo "<div class='stats'>";
            echo "<div class='stat-box'><div class='stat-number'>" . count($statusFiles) . "</div><div class='stat-label'>Arquivos Encontrados</div></div>";
            
            if (file_exists(__DIR__ . '/database/consultas.db')) {
                try {
                    require_once __DIR__ . '/database.php';
                    $db = new ConsultaDatabase();
                    $stats = $db->getStats();
                    echo "<div class='stat-box'><div class='stat-number'>" . ($stats['total'] ?? 0) . "</div><div class='stat-label'>No Banco</div></div>";
                } catch (Exception $e) {
                    echo "<div class='stat-box'><div class='stat-number'>0</div><div class='stat-label'>No Banco</div></div>";
                }
            } else {
                echo "<div class='stat-box'><div class='stat-number'>0</div><div class='stat-label'>No Banco</div></div>";
            }
            
            echo "<div class='stat-box'><div class='stat-number'>" . (count($statusFiles) - (isset($stats) ? ($stats['total'] ?? 0) : 0)) . "</div><div class='stat-label'>Para Importar</div></div>";
            echo "</div>";
            
            if (count($statusFiles) > 0) {
                echo "<form method='POST'>";
                echo "<input type='hidden' name='execute' value='1'>";
                echo "<button type='submit' class='btn'>🚀 Executar Migração</button>";
                echo "</form>";
            } else {
                echo "<p class='info'>ℹ️ Nenhum arquivo antigo encontrado para migrar.</p>";
            }
            
            echo "<p><a href='check_migration.php' class='btn'>Verificar Status</a> <a href='historico.php' class='btn'>Ver Histórico</a></p>";
        }
        ?>
    </div>
</body>
</html>

