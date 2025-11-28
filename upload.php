<?php
header('Content-Type: application/json');

// Configurações
$uploadDir = __DIR__ . '/uploads/';
$maxFileSize = 10 * 1024 * 1024; // 10MB
$allowedExtensions = ['csv', 'txt'];

// Função para converter tamanho para bytes
function convertToBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

// Criar diretório de uploads se não existir
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Verificar se o arquivo foi enviado
if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado']);
    exit;
}

$file = $_FILES['file'];

// Validar erro de upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'Arquivo excede o tamanho máximo permitido',
        UPLOAD_ERR_FORM_SIZE => 'Arquivo excede o tamanho máximo do formulário',
        UPLOAD_ERR_PARTIAL => 'Arquivo foi enviado parcialmente',
        UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta pasta temporária',
        UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever arquivo no disco',
        UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
    ];
    
    echo json_encode([
        'success' => false,
        'message' => $errorMessages[$file['error']] ?? 'Erro desconhecido no upload'
    ]);
    exit;
}

// Verificar limite do PHP
$phpUploadMax = ini_get('upload_max_filesize');
$phpPostMax = ini_get('post_max_size');
$phpUploadMaxBytes = convertToBytes($phpUploadMax);
$phpPostMaxBytes = convertToBytes($phpPostMax);
$actualMaxSize = min($maxFileSize, $phpUploadMaxBytes, $phpPostMaxBytes);

// Validar tamanho do arquivo
if ($file['size'] > $actualMaxSize) {
    $maxSizeMB = round($actualMaxSize / (1024 * 1024), 2);
    $fileSizeMB = round($file['size'] / (1024 * 1024), 2);
    echo json_encode([
        'success' => false, 
        'message' => "Arquivo muito grande ({$fileSizeMB}MB). Tamanho máximo permitido: {$maxSizeMB}MB. (Limite PHP: upload_max_filesize={$phpUploadMax}, post_max_size={$phpPostMax})"
    ]);
    exit;
}

// Validar extensão
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode(['success' => false, 'message' => 'Extensão não permitida. Use CSV ou TXT']);
    exit;
}

// Gerar nome único para o arquivo
$jobId = uniqid('job_', true);
$fileName = $jobId . '_' . basename($file['name']);
$filePath = $uploadDir . $fileName;

// Verificar se o diretório existe e é gravável
if (!is_dir($uploadDir)) {
    if (!@mkdir($uploadDir, 0775, true)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Não foi possível criar o diretório de uploads. Verifique as permissões do diretório pai.'
        ]);
        exit;
    }
}

// Tentar tornar o diretório gravável se não for
if (!is_writable($uploadDir)) {
    // Tenta diferentes níveis de permissão
    @chmod($uploadDir, 0777);
    
    // Se ainda não funcionar, tenta 0775
    if (!is_writable($uploadDir)) {
        @chmod($uploadDir, 0775);
    }
    
    // Verifica novamente
    if (!is_writable($uploadDir)) {
        $currentPerms = substr(sprintf('%o', fileperms($uploadDir)), -4);
        $owner = fileowner($uploadDir);
        $group = filegroup($uploadDir);
        $ownerInfo = function_exists('posix_getpwuid') ? posix_getpwuid($owner) : null;
        $groupInfo = function_exists('posix_getgrgid') ? posix_getgrgid($group) : null;
        $ownerName = $ownerInfo ? $ownerInfo['name'] : "UID:$owner";
        $groupName = $groupInfo ? $groupInfo['name'] : "GID:$group";
        
        echo json_encode([
            'success' => false, 
            'message' => "Diretório de uploads não tem permissão de escrita. Permissões: {$currentPerms}, Dono: {$ownerName}, Grupo: {$groupName}. Execute no servidor: chmod 777 uploads/"
        ]);
        exit;
    }
}

// Verificar se o arquivo temporário existe e é legível
if (!file_exists($file['tmp_name'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Arquivo temporário não encontrado. O upload pode ter falhado. Tente novamente.'
    ]);
    exit;
}

if (!is_readable($file['tmp_name'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Arquivo temporário não pode ser lido. Verifique as permissões do servidor.'
    ]);
    exit;
}

// Verificar espaço em disco
$freeSpace = disk_free_space($uploadDir);
if ($freeSpace !== false && $freeSpace < $file['size']) {
    echo json_encode([
        'success' => false, 
        'message' => 'Espaço em disco insuficiente no servidor.'
    ]);
    exit;
}

// Mover arquivo para o diretório de uploads
$moveResult = @move_uploaded_file($file['tmp_name'], $filePath);

if (!$moveResult) {
    $error = error_get_last();
    $errorMsg = 'Erro ao salvar arquivo no servidor';
    
    // Diagnóstico detalhado
    $diagnostics = [];
    
    if (!is_writable($uploadDir)) {
        $diagnostics[] = 'Diretório sem permissão de escrita';
    }
    
    if (!is_writable(dirname($filePath))) {
        $diagnostics[] = 'Diretório pai sem permissão';
    }
    
    if (file_exists($filePath)) {
        $diagnostics[] = 'Arquivo destino já existe';
    }
    
    if ($error) {
        $diagnostics[] = 'Erro PHP: ' . $error['message'];
    }
    
    if (!empty($diagnostics)) {
        $errorMsg .= ' (' . implode(', ', $diagnostics) . ')';
    }
    
    echo json_encode(['success' => false, 'message' => $errorMsg]);
    exit;
}

// Criar arquivo de status inicial
$statusDir = __DIR__ . '/status/';
if (!is_dir($statusDir)) {
    mkdir($statusDir, 0755, true);
}

$statusFile = $statusDir . $jobId . '.json';
$initialStatus = [
    'job_id' => $jobId,
    'status' => 'queued',
    'file_path' => $filePath,
    'file_name' => $file['name'],
    'created_at' => date('Y-m-d H:i:s'),
    'processed' => 0,
    'total' => 0,
    'progress' => 0,
    'message' => 'Aguardando processamento'
];

file_put_contents($statusFile, json_encode($initialStatus, JSON_PRETTY_PRINT));

// Iniciar processamento Python em background
$pythonScript = __DIR__ . '/process_batch.py';
$command = sprintf(
    'python3 %s %s %s > /dev/null 2>&1 &',
    escapeshellarg($pythonScript),
    escapeshellarg($filePath),
    escapeshellarg($jobId)
);

exec($command);

echo json_encode([
    'success' => true,
    'message' => 'Arquivo enviado com sucesso',
    'job_id' => $jobId
]);
?>

