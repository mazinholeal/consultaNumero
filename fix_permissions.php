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
            if (@chmod($dbFile, 0666)) {
                echo "<p class='success'>✅ Permissões do arquivo corrigidas para 666</p>";
            } else {
                echo "<p class='error'>❌ Erro ao alterar permissões do arquivo</p>";
                echo "<p class='info'>Execute no servidor: <code>chmod 666 database/consultas.db</code></p>";
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
        
        echo "<h2>7. Testando escrita no banco...</h2>";
        try {
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
            } catch (Exception $e) {
                echo "<p class='error'>❌ Erro ao escrever: " . htmlspecialchars($e->getMessage()) . "</p>";
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

