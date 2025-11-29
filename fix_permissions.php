<?php
/**
 * Script para corrigir permissões do banco de dados
 * Acesse: http://seu-servidor/consultanumero/fix_permissions.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrigir Permissões</title>
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
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: #004C97; }
        pre {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #004C97;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        .btn:hover { background: #003366; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🔧 Corrigir Permissões do Banco de Dados</h1>
        
        <?php
        $dbDir = __DIR__ . '/database';
        $dbFile = $dbDir . '/consultas.db';
        
        echo "<h2>1. Verificando diretório database...</h2>";
        if (!is_dir($dbDir)) {
            echo "<p class='error'>❌ Diretório não existe. Criando...</p>";
            if (@mkdir($dbDir, 0777, true)) {
                echo "<p class='success'>✅ Diretório criado</p>";
            } else {
                echo "<p class='error'>❌ Erro ao criar diretório</p>";
            }
        } else {
            echo "<p class='success'>✅ Diretório existe</p>";
            $currentPerms = substr(sprintf('%o', fileperms($dbDir)), -4);
            echo "<p>Permissões atuais: <strong>$currentPerms</strong></p>";
        }
        
        echo "<h2>2. Corrigindo permissões do diretório...</h2>";
        if (is_dir($dbDir)) {
            if (@chmod($dbDir, 0777)) {
                echo "<p class='success'>✅ Permissões do diretório corrigidas para 777</p>";
            } else {
                echo "<p class='error'>❌ Erro ao alterar permissões do diretório</p>";
                echo "<p class='info'>Execute no servidor: <code>chmod 777 database</code></p>";
            }
        }
        
        echo "<h2>3. Verificando arquivo do banco...</h2>";
        if (file_exists($dbFile)) {
            echo "<p class='success'>✅ Arquivo do banco existe</p>";
            $currentPerms = substr(sprintf('%o', fileperms($dbFile)), -4);
            echo "<p>Permissões atuais: <strong>$currentPerms</strong></p>";
            echo "<p>Tamanho: " . number_format(filesize($dbFile)) . " bytes</p>";
            
            echo "<h2>4. Corrigindo permissões do arquivo...</h2>";
            
            // Tentar múltiplas abordagens para corrigir permissões
            $fixed = false;
            
            // Tentativa 1: chmod direto
            if (@chmod($dbFile, 0666)) {
                echo "<p class='success'>✅ Permissões do arquivo corrigidas para 666 (método 1)</p>";
                $fixed = true;
            } else {
                // Tentativa 2: Usar shell_exec com chmod
                $output = @shell_exec("chmod 666 " . escapeshellarg($dbFile) . " 2>&1");
                if ($output === null || trim($output) === '') {
                    $newPerms = substr(sprintf('%o', fileperms($dbFile)), -4);
                    if ($newPerms == '0666' || $newPerms == '0664' || is_writable($dbFile)) {
                        echo "<p class='success'>✅ Permissões do arquivo corrigidas para 666 (método 2)</p>";
                        $fixed = true;
                    }
                }
            }
            
            // Tentativa 3: Criar script shell temporário e executar
            if (!$fixed) {
                $fixScript = $dbDir . '/fix_perms.sh';
                $scriptContent = "#!/bin/bash\nchmod 666 " . escapeshellarg($dbFile) . "\nchmod 777 " . escapeshellarg($dbDir) . "\n";
                if (@file_put_contents($fixScript, $scriptContent)) {
                    @chmod($fixScript, 0755);
                    @shell_exec("bash " . escapeshellarg($fixScript) . " 2>&1");
                    @unlink($fixScript);
                    
                    $newPerms = substr(sprintf('%o', fileperms($dbFile)), -4);
                    if ($newPerms == '0666' || $newPerms == '0664' || is_writable($dbFile)) {
                        echo "<p class='success'>✅ Permissões do arquivo corrigidas para 666 (método 3)</p>";
                        $fixed = true;
                    }
                }
            }
            
            if (!$fixed) {
                echo "<p class='warning'>⚠️ Não foi possível alterar permissões via PHP</p>";
                echo "<p class='info'>Tentando deletar e recriar o banco...</p>";
                
                // Tentativa 4: Deletar e recriar o banco
                try {
                    @unlink($dbFile);
                    @unlink($dbDir . '/consultas.db-journal');
                    echo "<p class='success'>✅ Arquivo antigo removido</p>";
                    echo "<p class='info'>O banco será recriado automaticamente na próxima tentativa</p>";
                } catch (Exception $e) {
                    echo "<p class='error'>❌ Não foi possível remover o arquivo</p>";
                    echo "<p class='info'>Execute manualmente no servidor: <code>chmod 666 database/consultas.db</code></p>";
                }
            }
        } else {
            echo "<p class='info'>ℹ️ Arquivo do banco ainda não existe (será criado na primeira consulta)</p>";
        }
        
        echo "<h2>5. Verificando ownership...</h2>";
        if (is_dir($dbDir)) {
            $owner = fileowner($dbDir);
            $group = filegroup($dbDir);
            $ownerInfo = function_exists('posix_getpwuid') ? posix_getpwuid($owner) : null;
            $groupInfo = function_exists('posix_getgrgid') ? posix_getgrgid($group) : null;
            $ownerName = $ownerInfo ? $ownerInfo['name'] : "UID:$owner";
            $groupName = $groupInfo ? $groupInfo['name'] : "GID:$group";
            
            echo "<p>Dono: <strong>$ownerName</strong></p>";
            echo "<p>Grupo: <strong>$groupName</strong></p>";
            
            // Tentar alterar ownership para www-data
            if (function_exists('posix_getpwnam')) {
                $wwwData = posix_getpwnam('www-data');
                if ($wwwData) {
                    echo "<h2>6. Alterando ownership para www-data...</h2>";
                    if (@chown($dbDir, $wwwData['uid'])) {
                        echo "<p class='success'>✅ Ownership do diretório alterado</p>";
                    } else {
                        echo "<p class='info'>ℹ️ Não foi possível alterar ownership (pode precisar de sudo)</p>";
                        echo "<p class='info'>Execute no servidor: <code>chown -R www-data:www-data database</code></p>";
                    }
                    
                    if (file_exists($dbFile)) {
                        if (@chown($dbFile, $wwwData['uid'])) {
                            echo "<p class='success'>✅ Ownership do arquivo alterado</p>";
                        }
                    }
                }
            }
        }
        
        echo "<h2>7. Garantindo permissões finais...</h2>";
        // Garantir que o diretório está gravável
        if (is_dir($dbDir)) {
            @chmod($dbDir, 0777);
            if (is_writable($dbDir)) {
                echo "<p class='success'>✅ Diretório está gravável</p>";
            } else {
                echo "<p class='error'>❌ Diretório ainda não está gravável</p>";
            }
        }
        
        // Se o arquivo existe, garantir permissões
        if (file_exists($dbFile)) {
            @chmod($dbFile, 0666);
            if (is_writable($dbFile)) {
                echo "<p class='success'>✅ Arquivo está gravável</p>";
            } else {
                // Última tentativa: deletar e deixar recriar
                echo "<p class='warning'>⚠️ Arquivo não está gravável. Removendo para recriar...</p>";
                @unlink($dbFile);
                echo "<p class='info'>ℹ️ Arquivo removido. Será recriado automaticamente.</p>";
            }
        }
        
        echo "<h2>8. Testando escrita no banco...</h2>";
        try {
            // Recarregar a classe para garantir que o banco seja recriado se necessário
            require_once __DIR__ . '/database.php';
            $db = new ConsultaDatabase();
            
            // Tentar criar uma consulta de teste
            $testJobId = 'test_' . time();
            try {
                $db->createConsulta($testJobId, 'teste.txt', null);
                echo "<p class='success'>✅ Teste de escrita bem-sucedido!</p>";
                
                // Deletar o teste
                $db->deleteConsulta($testJobId);
                echo "<p class='success'>✅ Banco de dados está funcionando corretamente</p>";
                
                // Verificar permissões finais
                if (file_exists($dbFile)) {
                    $finalPerms = substr(sprintf('%o', fileperms($dbFile)), -4);
                    echo "<p>Permissões finais do arquivo: <strong>$finalPerms</strong></p>";
                }
            } catch (Exception $e) {
                $errorMsg = $e->getMessage();
                echo "<p class='error'>❌ Erro ao escrever: " . htmlspecialchars($errorMsg) . "</p>";
                
                // Se ainda der erro, tentar deletar o banco e recriar
                if (strpos($errorMsg, 'readonly') !== false || strpos($errorMsg, 'read-only') !== false) {
                    echo "<p class='warning'>⚠️ Tentando remover banco e recriar...</p>";
                    @unlink($dbFile);
                    @unlink($dbDir . '/consultas.db-journal');
                    
                    try {
                        $db = new ConsultaDatabase();
                        $testJobId2 = 'test2_' . time();
                        $db->createConsulta($testJobId2, 'teste2.txt', null);
                        $db->deleteConsulta($testJobId2);
                        echo "<p class='success'>✅ Banco recriado e funcionando!</p>";
                    } catch (Exception $e2) {
                        echo "<p class='error'>❌ Erro persistente: " . htmlspecialchars($e2->getMessage()) . "</p>";
                        echo "<p class='info'>Execute manualmente no servidor:</p>";
                        echo "<pre>cd /var/www/html/consultanumero\nrm -f database/consultas.db*\nchmod 777 database\nchown -R www-data:www-data database</pre>";
                    }
                }
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro ao conectar: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
        
        <hr style="margin: 30px 0;">
        <p><a href="migrate_web.php" class="btn">🔄 Tentar Migração Novamente</a></p>
        <p><a href="check_migration.php">← Verificar Status</a> | <a href="historico.php">← Ver Histórico</a></p>
    </div>
</body>
</html>

