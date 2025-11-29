<?php
/**
 * Script de migração para importar resultados antigos para o banco SQLite
 * Execute: php migrate_old_results.php
 */

require_once __DIR__ . '/database.php';

echo "========================================\n";
echo "Migração de Resultados Antigos\n";
echo "========================================\n\n";

$db = new ConsultaDatabase();
$statusDir = __DIR__ . '/status/';
$resultsDir = __DIR__ . '/results/';
$uploadsDir = __DIR__ . '/uploads/';

if (!is_dir($statusDir)) {
    echo "Diretório de status não encontrado!\n";
    exit(1);
}

// Buscar todos os arquivos de status
$statusFiles = [];
if (is_dir($statusDir)) {
    $files = glob($statusDir . '*.json');
    $statusFiles = array_filter($files, function($file) {
        return !preg_match('/_(checkpoint|errors)\.json$/', $file);
    });
}

// Se não houver arquivos de status mas houver arquivos de resultados, criar registros a partir dos resultados
$resultsFiles = [];
if (is_dir($resultsDir)) {
    $resultsFiles = glob($resultsDir . '*.json');
    $resultsFiles = array_filter($resultsFiles, function($file) {
        return basename($file) !== '.gitkeep';
    });
}

$imported = 0;
$skipped = 0;
$errors = 0;

// Se não há arquivos de status mas há resultados, criar registros a partir dos resultados
if (empty($statusFiles) && !empty($resultsFiles)) {
    echo "⚠️  Nenhum arquivo de status encontrado, mas há " . count($resultsFiles) . " arquivo(s) de resultados.\n";
    echo "Criando registros a partir dos arquivos de resultados...\n\n";
    
    foreach ($resultsFiles as $resultsFile) {
        $jobId = basename($resultsFile, '.json');
        
        // Verificar se já existe no banco
        $existing = $db->getConsulta($jobId);
        if ($existing) {
            echo "⏭️  Já existe: $jobId\n";
            $skipped++;
            continue;
        }
        
        $results = json_decode(file_get_contents($resultsFile), true);
        $totalResults = is_array($results) ? count($results) : 0;
        
        // Tentar encontrar arquivo de upload
        $filePath = null;
        $fileName = 'arquivo_desconhecido.txt';
        $uploadFiles = glob($uploadsDir . $jobId . '_*');
        if (!empty($uploadFiles)) {
            $filePath = $uploadFiles[0];
            $fileName = basename($filePath);
            $fileName = preg_replace('/^' . preg_quote($jobId, '/') . '_/', '', $fileName);
        }
        
        try {
            $db->createConsulta($jobId, $fileName, $filePath);
            $db->updateConsulta($jobId, [
                'status' => 'completed',
                'total' => $totalResults,
                'processed' => $totalResults,
                'progress' => 100,
                'errors_count' => 0,
                'message' => 'Migrado de arquivo de resultados'
            ]);
            
            echo "✅ Criado a partir de resultados: $jobId - $fileName ($totalResults resultados)\n";
            $imported++;
        } catch (Exception $e) {
            echo "❌ Erro ao criar $jobId: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    echo "\n========================================\n";
    echo "Migração Concluída!\n";
    echo "========================================\n";
    echo "✅ Importados: $imported\n";
    echo "⏭️  Ignorados (já existentes): $skipped\n";
    echo "❌ Erros: $errors\n";
    exit(0);
}

if (empty($statusFiles)) {
    echo "Nenhum arquivo de status encontrado e nenhum arquivo de resultados para migrar!\n";
    exit(0);
}

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
            // Remover prefixo do job_id do nome
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
                'status' => 'completed',
                'completed_at' => $statusData['updated_at'] ?? $statusData['created_at'] ?? date('Y-m-d H:i:s')
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
echo "\nAcesse historico.php para ver os resultados.\n";

