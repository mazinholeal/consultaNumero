<?php
/**
 * Gerenciamento de banco de dados SQLite para histórico de consultas
 * Se SQLite não estiver disponível, usa versão fallback
 */

// Se SQLite não estiver disponível, usar versão fallback
if (!extension_loaded('pdo_sqlite')) {
    if (!class_exists('ConsultaDatabase', false)) {
        require_once __DIR__ . '/database_fallback.php';
    }
    return; // Retorna antes de declarar a classe SQLite
}

// Verificar se a classe já foi declarada (fallback carregado)
if (class_exists('ConsultaDatabase', false)) {
    return;
}

class ConsultaDatabase {
    private $db;
    private $dbPath;
    
    public function __construct() {
        $this->dbPath = __DIR__ . '/database/consultas.db';
        $dbDir = dirname($this->dbPath);
        
        // Criar diretório se não existir
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
            chmod($dbDir, 0777);
        } else {
            // Garantir permissões de escrita
            if (!is_writable($dbDir)) {
                chmod($dbDir, 0777);
            }
        }
        
        // Se o arquivo do banco existe, garantir permissões de escrita
        if (file_exists($this->dbPath) && !is_writable($this->dbPath)) {
            chmod($this->dbPath, 0666);
        }
        
        // Conectar ao banco
        try {
            $this->db = new PDO('sqlite:' . $this->dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->createTables();
        } catch (PDOException $e) {
            error_log("Erro ao conectar ao banco: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function createTables() {
        $sql = "CREATE TABLE IF NOT EXISTS consultas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_id TEXT UNIQUE NOT NULL,
            file_name TEXT NOT NULL,
            file_path TEXT,
            status TEXT NOT NULL DEFAULT 'queued',
            total INTEGER DEFAULT 0,
            processed INTEGER DEFAULT 0,
            progress INTEGER DEFAULT 0,
            errors_count INTEGER DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME,
            completed_at DATETIME,
            message TEXT
        )";
        
        $this->db->exec($sql);
    }
    
    public function createConsulta($jobId, $fileName, $filePath) {
        $sql = "INSERT INTO consultas (job_id, file_name, file_path, status, created_at)
                VALUES (:job_id, :file_name, :file_path, 'queued', datetime('now'))";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':job_id' => $jobId,
            ':file_name' => $fileName,
            ':file_path' => $filePath
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function updateConsulta($jobId, $data) {
        $fields = [];
        $values = [':job_id' => $jobId];
        
        if (isset($data['status'])) {
            $fields[] = "status = :status";
            $values[':status'] = $data['status'];
        }
        if (isset($data['total'])) {
            $fields[] = "total = :total";
            $values[':total'] = $data['total'];
        }
        if (isset($data['processed'])) {
            $fields[] = "processed = :processed";
            $values[':processed'] = $data['processed'];
        }
        if (isset($data['progress'])) {
            $fields[] = "progress = :progress";
            $values[':progress'] = $data['progress'];
        }
        if (isset($data['errors_count'])) {
            $fields[] = "errors_count = :errors_count";
            $values[':errors_count'] = $data['errors_count'];
        }
        if (isset($data['message'])) {
            $fields[] = "message = :message";
            $values[':message'] = $data['message'];
        }
        
        if ($data['status'] === 'completed' || $data['status'] === 'error') {
            $fields[] = "completed_at = datetime('now')";
        }
        
        $fields[] = "updated_at = datetime('now')";
        
        $sql = "UPDATE consultas SET " . implode(', ', $fields) . " WHERE job_id = :job_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    public function getConsulta($jobId) {
        $sql = "SELECT * FROM consultas WHERE job_id = :job_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':job_id' => $jobId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAllConsultas($limit = 50, $offset = 0) {
        $sql = "SELECT * FROM consultas ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function deleteConsulta($jobId) {
        // Deletar arquivos relacionados
        $consulta = $this->getConsulta($jobId);
        if ($consulta) {
            // Deletar arquivo de upload
            if ($consulta['file_path'] && file_exists($consulta['file_path'])) {
                @unlink($consulta['file_path']);
            }
            
            // Deletar arquivos de resultado e status
            $baseDir = __DIR__;
            @unlink($baseDir . '/results/' . $jobId . '.json');
            @unlink($baseDir . '/status/' . $jobId . '.json');
            @unlink($baseDir . '/status/' . $jobId . '_checkpoint.json');
            @unlink($baseDir . '/status/' . $jobId . '_errors.json');
        }
        
        // Deletar do banco
        $sql = "DELETE FROM consultas WHERE job_id = :job_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':job_id' => $jobId]);
    }
    
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors,
                    SUM(total) as total_numbers,
                    SUM(processed) as processed_numbers
                FROM consultas";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

