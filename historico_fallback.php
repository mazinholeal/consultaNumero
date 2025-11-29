<?php
/**
 * Versão do histórico que funciona sem SQLite
 * Usa apenas arquivos JSON existentes
 */

// Buscar arquivos de status
$statusDir = __DIR__ . '/status/';
$resultsDir = __DIR__ . '/results/';
$uploadsDir = __DIR__ . '/uploads/';

$consultas = [];

if (is_dir($statusDir)) {
    $statusFiles = glob($statusDir . '*.json');
    $statusFiles = array_filter($statusFiles, function($file) {
        return !preg_match('/_(checkpoint|errors)\.json$/', $file);
    });
    
    foreach ($statusFiles as $statusFile) {
        $data = json_decode(file_get_contents($statusFile), true);
        if ($data && isset($data['job_id'])) {
            $jobId = $data['job_id'];
            
            // Contar resultados
            $totalResults = 0;
            $resultsFile = $resultsDir . $jobId . '.json';
            if (file_exists($resultsFile)) {
                $results = json_decode(file_get_contents($resultsFile), true);
                if (is_array($results)) {
                    $totalResults = count($results);
                }
            }
            
            // Buscar arquivo de upload
            $fileName = $data['file_name'] ?? 'arquivo_desconhecido.txt';
            $uploadFiles = glob($uploadsDir . $jobId . '_*');
            if (!empty($uploadFiles)) {
                $fileName = basename($uploadFiles[0]);
                $fileName = preg_replace('/^' . preg_quote($jobId, '/') . '_/', '', $fileName);
            }
            
            $consultas[] = [
                'job_id' => $jobId,
                'file_name' => $fileName,
                'status' => $data['status'] ?? 'unknown',
                'total' => $data['total'] ?? $totalResults,
                'processed' => $data['processed'] ?? $totalResults,
                'progress' => $data['progress'] ?? ($totalResults > 0 ? 100 : 0),
                'errors_count' => $data['errors_count'] ?? 0,
                'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s', filemtime($statusFile))
            ];
        }
    }
}

// Ordenar por data (mais recente primeiro)
usort($consultas, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Calcular estatísticas
$stats = [
    'total' => count($consultas),
    'completed' => count(array_filter($consultas, fn($c) => $c['status'] === 'completed')),
    'processing' => count(array_filter($consultas, fn($c) => $c['status'] === 'processing')),
    'errors' => count(array_filter($consultas, fn($c) => $c['status'] === 'error'))
];

// Processar delete se solicitado
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $jobId = $_GET['delete'];
    $deleted = false;
    
    // Deletar arquivos relacionados
    @unlink($statusDir . $jobId . '.json');
    @unlink($statusDir . $jobId . '_checkpoint.json');
    @unlink($statusDir . $jobId . '_errors.json');
    @unlink($resultsDir . $jobId . '.json');
    
    $uploadFiles = glob($uploadsDir . $jobId . '_*');
    foreach ($uploadFiles as $file) {
        @unlink($file);
    }
    
    header('Location: historico.php?deleted=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Consultas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #004C97 0%, #003366 100%);
            background-attachment: fixed;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 16px 0 rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body class="min-h-screen py-4 px-4 md:px-6">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="glass-effect rounded-xl p-4 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-[#004C97] mb-1">
                        <i class="fas fa-history mr-2"></i>
                        Histórico de Consultas
                    </h1>
                    <p class="text-gray-600 text-sm">Gerencie todas as consultas em lote realizadas</p>
                    <p class="text-xs text-orange-600 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Modo fallback (sem SQLite) - Instale php-sqlite3 para funcionalidade completa
                    </p>
                </div>
                <a href="index.php" class="px-4 py-2 bg-[#004C97] text-white rounded-lg hover:bg-[#003366] transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Voltar
                </a>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="glass-effect rounded-xl p-4 mb-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-3 bg-blue-50 rounded-lg">
                    <div class="text-2xl font-bold text-[#004C97]"><?php echo $stats['total']; ?></div>
                    <div class="text-xs text-gray-600">Total</div>
                </div>
                <div class="text-center p-3 bg-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-600"><?php echo $stats['completed']; ?></div>
                    <div class="text-xs text-gray-600">Concluídas</div>
                </div>
                <div class="text-center p-3 bg-yellow-50 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['processing']; ?></div>
                    <div class="text-xs text-gray-600">Processando</div>
                </div>
                <div class="text-center p-3 bg-red-50 rounded-lg">
                    <div class="text-2xl font-bold text-red-600"><?php echo $stats['errors']; ?></div>
                    <div class="text-xs text-gray-600">Com Erro</div>
                </div>
            </div>
        </div>

        <!-- Lista de Consultas -->
        <div class="glass-effect rounded-xl p-6">
            <?php if (isset($_GET['deleted'])): ?>
                <div class="mb-4 p-3 bg-green-50 text-green-800 rounded-lg border-l-4 border-green-500">
                    <i class="fas fa-check-circle mr-2"></i>Consulta deletada com sucesso!
                </div>
            <?php endif; ?>

            <?php if (empty($consultas)): ?>
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-3 block"></i>
                    <p>Nenhuma consulta encontrada</p>
                    <a href="index.php" class="text-[#004C97] hover:underline mt-2 inline-block">Fazer primeira consulta</a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-[#004C97] text-white">
                                <th class="p-3 text-left">Data</th>
                                <th class="p-3 text-left">Arquivo</th>
                                <th class="p-3 text-left">Status</th>
                                <th class="p-3 text-left">Progresso</th>
                                <th class="p-3 text-left">Números</th>
                                <th class="p-3 text-left">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($consultas as $consulta): ?>
                                <?php
                                $statusClass = [
                                    'completed' => 'bg-green-100 text-green-800',
                                    'processing' => 'bg-yellow-100 text-yellow-800',
                                    'error' => 'bg-red-100 text-red-800',
                                    'queued' => 'bg-gray-100 text-gray-800'
                                ];
                                $statusText = [
                                    'completed' => 'Concluído',
                                    'processing' => 'Processando',
                                    'error' => 'Erro',
                                    'queued' => 'Na Fila'
                                ];
                                $status = $consulta['status'] ?? 'queued';
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="p-3 text-gray-700">
                                        <?php echo date('d/m/Y H:i', strtotime($consulta['created_at'])); ?>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($consulta['file_name']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($consulta['job_id']); ?></div>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 rounded text-xs font-semibold <?php echo $statusClass[$status] ?? $statusClass['queued']; ?>">
                                            <?php echo $statusText[$status] ?? 'Desconhecido'; ?>
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full transition-all <?php 
                                                $progress = $consulta['progress'] ?? 0;
                                                if ($progress < 25) echo 'bg-[#E30613]'; // Vermelho TIM
                                                elseif ($progress < 50) echo 'bg-[#FFD100]'; // Amarelo TIM
                                                elseif ($progress < 75) echo 'bg-[#004C97]'; // Azul TIM
                                                else echo 'bg-green-600'; // Verde para completo
                                            ?>" style="width: <?php echo $progress; ?>%"></div>
                                        </div>
                                        <div class="text-xs text-gray-600 mt-1"><?php echo $progress; ?>%</div>
                                    </td>
                                    <td class="p-3 text-gray-700">
                                        <?php echo number_format($consulta['processed'] ?? 0); ?> / <?php echo number_format($consulta['total'] ?? 0); ?>
                                        <?php if ($consulta['errors_count'] > 0): ?>
                                            <div class="text-xs text-red-600">
                                                <i class="fas fa-exclamation-triangle"></i> <?php echo $consulta['errors_count']; ?> erro(s)
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3">
                                        <div class="flex gap-2">
                                            <?php if ($status === 'completed'): ?>
                                                <a href="results.php?job_id=<?php echo urlencode($consulta['job_id']); ?>" 
                                                   class="px-3 py-1 bg-[#004C97] text-white rounded text-xs hover:bg-[#003366]">
                                                    <i class="fas fa-eye mr-1"></i>Ver
                                                </a>
                                            <?php endif; ?>
                                            <a href="?delete=<?php echo urlencode($consulta['job_id']); ?>" 
                                               onclick="return confirm('Tem certeza que deseja deletar esta consulta? Todos os arquivos relacionados serão removidos.');"
                                               class="px-3 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">
                                                <i class="fas fa-trash mr-1"></i>Deletar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

