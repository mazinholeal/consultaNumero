<?php
header('Content-Type: application/json');

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

echo json_encode($status);
?>

