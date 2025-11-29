<?php
header('Content-Type: application/json');

require_once __DIR__ . '/database.php';

$jobId = $_GET['job_id'] ?? '';

if (empty($jobId)) {
    echo json_encode(['success' => false, 'message' => 'Job ID não fornecido']);
    exit;
}

$statusFile = __DIR__ . '/status/' . $jobId . '.json';

if (!file_exists($statusFile)) {
    echo json_encode(['success' => false, 'message' => 'Job não encontrado']);
    exit;
}

$status = json_decode(file_get_contents($statusFile), true);

if ($status === null) {
    echo json_encode(['success' => false, 'message' => 'Erro ao ler status']);
    exit;
}

// Atualizar banco de dados com status atual
try {
    $db = new ConsultaDatabase();
    $db->updateConsulta($jobId, [
        'status' => $status['status'] ?? 'unknown',
        'total' => $status['total'] ?? 0,
        'processed' => $status['processed'] ?? 0,
        'progress' => $status['progress'] ?? 0,
        'errors_count' => $status['errors_count'] ?? 0,
        'message' => $status['message'] ?? ''
    ]);
} catch (Exception $e) {
    error_log("Erro ao atualizar banco: " . $e->getMessage());
    // Continua mesmo se falhar
}

echo json_encode($status);
?>

