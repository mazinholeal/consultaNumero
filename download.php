<?php
$jobId = $_GET['job_id'] ?? '';
$format = $_GET['format'] ?? 'json';
$operadora = $_GET['operadora'] ?? ''; // TIM, VIVO, CLARO, OUTROS, ou vazio para todos

if (empty($jobId)) {
    die('Job ID não fornecido');
}

$resultsFile = __DIR__ . '/results/' . $jobId . '.json';

if (!file_exists($resultsFile)) {
    die('Arquivo de resultados não encontrado');
}

$results = json_decode(file_get_contents($resultsFile), true);

// Filtrar por operadora se especificado
if (!empty($operadora)) {
    $filteredResults = [];
    
    foreach ($results as $result) {
        $operadoraResult = strtoupper(trim($result['operadora'] ?? ''));
        
        // Normalizar nomes de operadoras
        $operadoraNormalizada = '';
        if (stripos($operadoraResult, 'TIM') !== false) {
            $operadoraNormalizada = 'TIM';
        } elseif (stripos($operadoraResult, 'VIVO') !== false || stripos($operadoraResult, 'TELEFONICA') !== false || stripos($operadoraResult, 'TELEFÔNICA') !== false) {
            $operadoraNormalizada = 'VIVO';
        } elseif (stripos($operadoraResult, 'CLARO') !== false) {
            $operadoraNormalizada = 'CLARO';
        } else {
            $operadoraNormalizada = 'OUTROS';
        }
        
        // Se for OUTROS, inclui tudo que não for TIM, VIVO ou CLARO
        if ($operadora === 'OUTROS') {
            if ($operadoraNormalizada === 'OUTROS') {
                $filteredResults[] = $result;
            }
        } else {
            // Para TIM, VIVO ou CLARO, compara exatamente
            if ($operadoraNormalizada === strtoupper($operadora)) {
                $filteredResults[] = $result;
            }
        }
    }
    
    $results = $filteredResults;
    
    // Nome do arquivo inclui operadora
    $filenameSuffix = '_' . strtolower($operadora);
} else {
    $filenameSuffix = '';
}

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="resultados' . $filenameSuffix . '_' . $jobId . '.csv"');
    
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
    header('Content-Disposition: attachment; filename="resultados' . $filenameSuffix . '_' . $jobId . '.json"');
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>

