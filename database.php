<?php
/**
 * Gerenciamento de histórico de consultas
 * Usa arquivos JSON para armazenar informações
 */

class ConsultaDatabase {
    private $dataDir;
    
    public function __construct() {
        $this->dataDir = __DIR__ . '/database/';
        
        // Criar diretório se não existir
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0777, true);
        }
    }
    
    private function getDataFile() {
        return $this->dataDir . 'consultas.json';
    }
    
    private function loadData() {
        $file = $this->getDataFile();
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            return is_array($data) ? $data : [];
        }
        return [];
    }
    
    private function saveData($data) {
        $file = $this->getDataFile();
        return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    public function createConsulta($jobId, $fileName, $filePath) {
        $data = $this->loadData();
        
        $data[$jobId] = [
            'job_id' => $jobId,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'status' => 'queued',
            'total' => 0,
            'processed' => 0,
            'progress' => 0,
            'errors_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => null,
            'completed_at' => null,
            'message' => ''
        ];
        
        $this->saveData($data);
        return true;
    }
    
    public function updateConsulta($jobId, $updateData) {
        $data = $this->loadData();
        
        if (!isset($data[$jobId])) {
            return false;
        }
        
        foreach ($updateData as $key => $value) {
            if ($key !== 'completed_at' || $value !== null) {
                $data[$jobId][$key] = $value;
            }
        }
        
        $data[$jobId]['updated_at'] = date('Y-m-d H:i:s');
        
        if (isset($updateData['status']) && ($updateData['status'] === 'completed' || $updateData['status'] === 'error')) {
            $data[$jobId]['completed_at'] = date('Y-m-d H:i:s');
        }
        
        $this->saveData($data);
        return true;
    }
    
    public function getConsulta($jobId) {
        $data = $this->loadData();
        return isset($data[$jobId]) ? $data[$jobId] : null;
    }
    
    public function getAllConsultas($limit = 50, $offset = 0) {
        $data = $this->loadData();
        $consultas = array_values($data);
        
        // Ordenar por data (mais recente primeiro)
        usort($consultas, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($consultas, $offset, $limit);
    }
    
    public function deleteConsulta($jobId) {
        $data = $this->loadData();
        
        if (!isset($data[$jobId])) {
            return false;
        }
        
        $consulta = $data[$jobId];
        
        // Deletar arquivos relacionados
        if (isset($consulta['file_path']) && file_exists($consulta['file_path'])) {
            @unlink($consulta['file_path']);
        }
        
        $baseDir = __DIR__;
        @unlink($baseDir . '/results/' . $jobId . '.json');
        @unlink($baseDir . '/status/' . $jobId . '.json');
        @unlink($baseDir . '/status/' . $jobId . '_checkpoint.json');
        @unlink($baseDir . '/status/' . $jobId . '_errors.json');
        
        // Remover do array
        unset($data[$jobId]);
        $this->saveData($data);
        
        return true;
    }
    
    public function getStats() {
        $data = $this->loadData();
        $consultas = array_values($data);
        
        $stats = [
            'total' => count($consultas),
            'completed' => 0,
            'processing' => 0,
            'errors' => 0,
            'total_numbers' => 0,
            'processed_numbers' => 0
        ];
        
        foreach ($consultas as $consulta) {
            $status = $consulta['status'] ?? 'unknown';
            if ($status === 'completed') $stats['completed']++;
            if ($status === 'processing') $stats['processing']++;
            if ($status === 'error') $stats['errors']++;
            
            $stats['total_numbers'] += $consulta['total'] ?? 0;
            $stats['processed_numbers'] += $consulta['processed'] ?? 0;
        }
        
        return $stats;
    }
}

