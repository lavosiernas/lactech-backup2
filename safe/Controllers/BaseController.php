<?php
/**
 * SafeNode - Base Controller
 * Classe base para todos os controllers
 */

namespace SafeNode\Controllers;

class BaseController
{
    protected $db;
    protected $viewPath;
    
    public function __construct($database)
    {
        $this->db = $database;
        $this->viewPath = __DIR__ . '/../../views/';
    }
    
    /**
     * Renderiza uma view
     */
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = $this->viewPath . $view . '.php';
        
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View não encontrada: {$view}");
        }
        
        require $viewFile;
    }
    
    /**
     * Retorna resposta JSON
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Redireciona para uma URL
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}









