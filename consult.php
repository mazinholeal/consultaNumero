<?php
header('Content-Type: application/json');

// Configurações
$API_URL = "https://painel.tridtelecom.com.br/_7port/consulta.php";
$MAX_NUMBERS = 100; // Limite de números por consulta individual

// Verificar se os números foram enviados
if (!isset($_POST['numbers']) || empty(trim($_POST['numbers']))) {
    echo json_encode(['success' => false, 'message' => 'Nenhum número fornecido']);
    exit;
}

$numbersInput = trim($_POST['numbers']);

// Processar números (separados por vírgula ou linha)
$numbers = [];
if (strpos($numbersInput, ',') !== false) {
    // Separados por vírgula
    $numbers = array_map('trim', explode(',', $numbersInput));
} else {
    // Separados por linha
    $numbers = array_filter(array_map('trim', explode("\n", $numbersInput)));
}

// Limpar e validar números
$cleanedNumbers = [];
foreach ($numbers as $num) {
    // Remove tudo exceto dígitos
    $cleaned = preg_replace('/\D/', '', $num);
    if (!empty($cleaned) && strlen($cleaned) >= 10) {
        $cleanedNumbers[] = $cleaned;
    }
}

if (empty($cleanedNumbers)) {
    echo json_encode(['success' => false, 'message' => 'Nenhum número válido encontrado']);
    exit;
}

// Limitar quantidade
if (count($cleanedNumbers) > $MAX_NUMBERS) {
    echo json_encode([
        'success' => false,
        'message' => "Máximo de {$MAX_NUMBERS} números por consulta individual. Use a consulta em lote para mais números."
    ]);
    exit;
}

// Dividir em lotes menores para evitar URLs muito longas
$BATCH_SIZE = 50;
$allResults = [];

for ($i = 0; $i < count($cleanedNumbers); $i += $BATCH_SIZE) {
    $batch = array_slice($cleanedNumbers, $i, $BATCH_SIZE);
    $numbersStr = implode(',', $batch);
    
    // Monta URL
    $url = $API_URL . '?numero=' . urlencode($numbersStr);
    
    // Realiza requisição
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; BatchConsult/1.0)');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        // Em caso de erro, adiciona erro para cada número do lote
        foreach ($batch as $num) {
            $allResults[] = [
                'numero' => $num,
                'erro' => 'Erro de conexão: ' . $curlError
            ];
        }
        continue;
    }
    
    if ($httpCode !== 200) {
        // Em caso de erro HTTP, adiciona erro para cada número do lote
        foreach ($batch as $num) {
            $allResults[] = [
                'numero' => $num,
                'erro' => "Erro HTTP {$httpCode}"
            ];
        }
        continue;
    }
    
    // Decodifica JSON
    $batchResults = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Erro ao decodificar JSON
        foreach ($batch as $num) {
            $allResults[] = [
                'numero' => $num,
                'erro' => 'Resposta inválida da API'
            ];
        }
        continue;
    }
    
    if (is_array($batchResults)) {
        $allResults = array_merge($allResults, $batchResults);
    } else {
        // Resposta inesperada
        foreach ($batch as $num) {
            $allResults[] = [
                'numero' => $num,
                'erro' => 'Formato de resposta inválido'
            ];
        }
    }
    
    // Pequeno delay entre requisições para evitar sobrecarga
    if ($i + $BATCH_SIZE < count($cleanedNumbers)) {
        usleep(500000); // 0.5 segundos
    }
}

// Garantir que todos os números tenham resultado
$resultNumbers = array_column($allResults, 'numero');
foreach ($cleanedNumbers as $num) {
    if (!in_array($num, $resultNumbers)) {
        $allResults[] = [
            'numero' => $num,
            'erro' => 'Número não encontrado na resposta'
        ];
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Consulta realizada com sucesso',
    'data' => $allResults,
    'total' => count($allResults)
]);
?>

