<?php
$jobId = $_GET['job_id'] ?? '';
$format = $_GET['format'] ?? 'json';

if (empty($jobId)) {
    die('Job ID não fornecido');
}

$resultsFile = __DIR__ . '/results/' . $jobId . '.json';

if (!file_exists($resultsFile)) {
    die('Arquivo de resultados não encontrado');
}

$results = json_decode(file_get_contents($resultsFile), true);

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="resultados_' . $jobId . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalho
    if (!empty($results)) {
        fputcsv($output, array_keys($results[0]), ';');
        
        // Dados
        foreach ($results as $result) {
            fputcsv($output, $result, ';');
        }
    }
    
    fclose($output);
} else {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="resultados_' . $jobId . '.json"');
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>

