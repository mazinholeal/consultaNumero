<?php
/**
 * Script para verificar e executar migração se necessário
 * Acesse via navegador: http://seu-servidor/consultanumero/check_migration.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Migração</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #004C97;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        .btn:hover { background: #003366; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🔍 Verificação de Migração</h1>
        
        <?php
        echo "<h2>1. Verificando módulo PHP SQLite...</h2>";
        if (extension_loaded('pdo_sqlite')) {
            echo "<p class='success'>✅ Módulo PDO SQLite está instalado</p>";
        } else {
            echo "<p class='error'>❌ Módulo PDO SQLite NÃO está instalado</p>";
            echo "<p>Execute no servidor: <code>apt-get install -y php-sqlite3 && systemctl restart apache2</code></p>";
        }
        
        echo "<h2>2. Verificando banco de dados...</h2>";
        $dbPath = __DIR__ . '/database/consultas.db';
        if (file_exists($dbPath)) {
            echo "<p class='success'>✅ Banco de dados existe: " . basename($dbPath) . "</p>";
            echo "<p>Tamanho: " . number_format(filesize($dbPath)) . " bytes</p>";
        } else {
            echo "<p class='warning'>⚠️ Banco de dados não existe ainda</p>";
        }
        
        echo "<h2>3. Verificando arquivos de status antigos...</h2>";
        $statusDir = __DIR__ . '/status/';
        $resultsDir = __DIR__ . '/results/';
        $uploadsDir = __DIR__ . '/uploads/';
        
        $statusFiles = [];
        $resultsFiles = [];
        $uploadFiles = [];
        
        if (is_dir($statusDir)) {
            $files = glob($statusDir . '*.json');
            $statusFiles = array_filter($files, function($file) {
                return !preg_match('/_(checkpoint|errors)\.json$/', $file);
            });
        }
        
        if (is_dir($resultsDir)) {
            $resultsFiles = glob($resultsDir . '*.json');
            $resultsFiles = array_filter($resultsFiles, function($file) {
                return basename($file) !== '.gitkeep';
            });
        }
        
        if (is_dir($uploadsDir)) {
            $uploadFiles = glob($uploadsDir . '*');
            $uploadFiles = array_filter($uploadFiles, function($file) {
                return is_file($file) && basename($file) !== '.gitkeep';
            });
        }
        
        echo "<p><strong>Arquivos de Status:</strong> " . count($statusFiles) . "</p>";
        echo "<p><strong>Arquivos de Resultados:</strong> " . count($resultsFiles) . "</p>";
        echo "<p><strong>Arquivos de Upload:</strong> " . count($uploadFiles) . "</p>";
        
        if (count($statusFiles) > 0) {
            echo "<h3>Arquivos de Status encontrados:</h3><ul>";
            foreach (array_slice($statusFiles, 0, 10) as $file) {
                $data = json_decode(file_get_contents($file), true);
                $jobId = $data['job_id'] ?? basename($file);
                $fileName = $data['file_name'] ?? 'N/A';
                $status = $data['status'] ?? 'unknown';
                echo "<li><strong>" . htmlspecialchars($jobId) . "</strong> - " . htmlspecialchars($fileName) . " (" . htmlspecialchars($status) . ")</li>";
            }
            if (count($statusFiles) > 10) {
                echo "<li>... e mais " . (count($statusFiles) - 10) . " arquivos</li>";
            }
            echo "</ul>";
        }
        
        if (count($resultsFiles) > 0 && count($statusFiles) == 0) {
            echo "<p class='warning'>⚠️ Há arquivos de resultados mas nenhum arquivo de status correspondente!</p>";
            echo "<p>Isso pode significar que os arquivos de status foram deletados ou estão em outro local.</p>";
            echo "<h3>Arquivos de Resultados encontrados:</h3><ul>";
            foreach (array_slice($resultsFiles, 0, 5) as $file) {
                $jobId = basename($file, '.json');
                echo "<li>" . htmlspecialchars($jobId) . "</li>";
            }
            if (count($resultsFiles) > 5) {
                echo "<li>... e mais " . (count($resultsFiles) - 5) . " arquivos</li>";
            }
            echo "</ul>";
        }
        
        if (!is_dir($statusDir)) {
            echo "<p class='error'>❌ Diretório de status não encontrado</p>";
        }
        
        echo "<h2>4. Verificando banco de dados (se existir)...</h2>";
        if (file_exists($dbPath)) {
            try {
                require_once __DIR__ . '/database.php';
                $db = new ConsultaDatabase();
                $consultas = $db->getAllConsultas(10);
                $stats = $db->getStats();
                
                echo "<p class='success'>✅ Conectado ao banco com sucesso</p>";
                echo "<p>Total de consultas no banco: <strong>" . ($stats['total'] ?? 0) . "</strong></p>";
                
                if (count($consultas) > 0) {
                    echo "<h3>Últimas consultas:</h3><ul>";
                    foreach ($consultas as $c) {
                        echo "<li>" . htmlspecialchars($c['job_id']) . " - " . htmlspecialchars($c['file_name']) . " (" . $c['status'] . ")</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p class='warning'>⚠️ Banco está vazio</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ Erro ao conectar: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        
        echo "<h2>5. Status da Migração</h2>";
        if (count($statusFiles) > 0 && (!file_exists($dbPath) || ($stats['total'] ?? 0) == 0)) {
            echo "<p class='warning'>⚠️ Há arquivos antigos mas o banco está vazio ou não existe</p>";
            echo "<p><strong>Ação necessária:</strong> Execute a migração</p>";
            echo "<a href='migrate_old_results.php' class='btn'>Executar Migração Agora</a>";
        } elseif (count($statusFiles) > 0 && file_exists($dbPath) && ($stats['total'] ?? 0) > 0) {
            echo "<p class='success'>✅ Migração parece estar completa</p>";
        } elseif (count($statusFiles) == 0) {
            echo "<p>ℹ️ Nenhum arquivo antigo encontrado para migrar</p>";
        }
        ?>
        
        <hr style="margin: 20px 0;">
        <p><a href="historico.php">← Voltar para Histórico</a> | <a href="index.php">← Voltar para Início</a></p>
    </div>
</body>
</html>

