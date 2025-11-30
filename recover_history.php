<?php
/**
 * Script de recuperação de histórico
 * Reconstrói o arquivo consultas.json a partir dos arquivos de status existentes
 */

require_once __DIR__ . '/database.php';

$statusDir = __DIR__ . '/status/';
$resultsDir = __DIR__ . '/results/';
$uploadsDir = __DIR__ . '/uploads/';

$recovered = 0;
$errors = 0;

echo "🔍 Recuperando histórico de consultas...\n\n";

// Buscar todos os arquivos de status
$statusFiles = glob($statusDir . '*.json');
$statusFiles = array_filter($statusFiles, function($file) {
    return !strpos(basename($file), '_checkpoint') && !strpos(basename($file), '_errors');
});

$db = new ConsultaDatabase();

foreach ($statusFiles as $statusFile) {
    $jobId = basename($statusFile, '.json');
    
    // Verificar se já existe no banco
    $existing = $db->getConsulta($jobId);
    if ($existing) {
        echo "⏭️  Job $jobId já existe, pulando...\n";
        continue;
    }
    
    // Ler arquivo de status
    $statusData = json_decode(file_get_contents($statusFile), true);
    if (!$statusData) {
        echo "❌ Erro ao ler $statusFile\n";
        $errors++;
        continue;
    }
    
    // Buscar arquivo de resultado
    $resultFile = $resultsDir . $jobId . '.json';
    $total = 0;
    if (file_exists($resultFile)) {
        $resultData = json_decode(file_get_contents($resultFile), true);
        if ($resultData && isset($resultData['results'])) {
            $total = count($resultData['results']);
        }
    }
    
    // Buscar arquivo de upload original
    $uploadFile = null;
    $fileName = 'arquivo_' . $jobId;
    if (file_exists($uploadsDir . $jobId . '.txt')) {
        $uploadFile = $uploadsDir . $jobId . '.txt';
        $fileName = basename($uploadFile);
    } elseif (file_exists($uploadsDir . $jobId . '.csv')) {
        $uploadFile = $uploadsDir . $jobId . '.csv';
        $fileName = basename($uploadFile);
    }
    
    // Criar entrada no banco
    $db->createConsulta(
        $jobId,
        $fileName,
        $uploadFile ?: ''
    );
    
    // Atualizar com dados do status
    $updateData = [
        'status' => $statusData['status'] ?? 'unknown',
        'total' => $statusData['total'] ?? $total,
        'processed' => $statusData['processed'] ?? 0,
        'progress' => $statusData['progress'] ?? 0,
        'errors_count' => $statusData['errors_count'] ?? 0,
        'message' => $statusData['message'] ?? ''
    ];
    
    if (isset($statusData['created_at'])) {
        // Manter data original se disponível
        $existing = $db->getConsulta($jobId);
        if ($existing) {
            $updateData['created_at'] = $statusData['created_at'];
        }
    }
    
    if (isset($statusData['status']) && ($statusData['status'] === 'completed' || $statusData['status'] === 'error')) {
        $updateData['completed_at'] = $statusData['completed_at'] ?? date('Y-m-d H:i:s');
    }
    
    $db->updateConsulta($jobId, $updateData);
    
    echo "✅ Recuperado: $jobId - {$updateData['status']} - {$updateData['processed']}/{$updateData['total']}\n";
    $recovered++;
}

echo "\n";
echo "==========================================\n";
echo "Recuperação concluída!\n";
echo "  - Recuperados: $recovered\n";
echo "  - Erros: $errors\n";
echo "==========================================\n";

