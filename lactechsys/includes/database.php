<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $supabase;
    
    private function __construct() {
        $this->supabase = $this->createSupabaseClient();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function createSupabaseClient() {
        // Simulação do cliente Supabase em PHP
        // Em produção, use a biblioteca oficial do Supabase para PHP
        return [
            'url' => SUPABASE_URL,
            'key' => SUPABASE_ANON_KEY,
            'headers' => [
                'apikey' => SUPABASE_ANON_KEY,
                'Authorization' => 'Bearer ' . SUPABASE_ANON_KEY,
                'Content-Type' => 'application/json'
            ]
        ];
    }
    
    public function getSupabase() {
        return $this->supabase;
    }
    
    public function query($table, $operation = 'select', $data = []) {
        // Implementação básica de query
        // Em produção, use a biblioteca oficial do Supabase
        $url = $this->supabase['url'] . '/rest/v1/' . $table;
        
        $options = [
            'http' => [
                'header' => implode("\r\n", array_map(
                    function($key, $value) {
                        return "$key: $value";
                    },
                    array_keys($this->supabase['headers']),
                    $this->supabase['headers']
                )),
                'method' => strtoupper($operation),
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        return json_decode($result, true);
    }
    
    public function select($table, $conditions = []) {
        return $this->query($table, 'select', $conditions);
    }
    
    public function insert($table, $data) {
        return $this->query($table, 'post', $data);
    }
    
    public function update($table, $data, $conditions = []) {
        return $this->query($table, 'patch', $data);
    }
    
    public function delete($table, $conditions = []) {
        return $this->query($table, 'delete', $conditions);
    }
}
?>
