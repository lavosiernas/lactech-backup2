<?php
require_once 'config.php';

class Auth {
    private $supabase_url;
    private $supabase_key;
    private $farm_id;
    
    public function __construct() {
        $this->supabase_url = SUPABASE_URL;
        $this->supabase_key = SUPABASE_ANON_KEY;
        $this->farm_id = FARM_ID; // Fazenda fixa
    }
    
    /**
     * Verifica se o usuário está logado
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Obtém o ID do usuário logado
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Obtém os dados do usuário logado
     */
    public function getUser() {
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Obtém o ID da fazenda (sempre a mesma)
     */
    public function getFarmId() {
        return $this->farm_id;
    }
    
    /**
     * Obtém o nome da fazenda
     */
    public function getFarmName() {
        return FARM_NAME;
    }
    
    /**
     * Define o usuário como logado
     */
    public function setUser($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;
    }
    
    /**
     * Faz logout do usuário
     */
    public function logout() {
        session_destroy();
        session_start();
    }
    
    /**
     * Verifica se o usuário tem permissão específica
     */
    public function hasPermission($permission) {
        $user = $this->getUser();
        if (!$user) return false;
        
        $role = $user['role'] ?? 'funcionario';
        
        // Mapeamento de permissões por role
        $permissions = [
            'proprietario' => ['all'],
            'gerente' => ['manage_users', 'view_reports', 'manage_production'],
            'veterinario' => ['view_animals', 'manage_health'],
            'funcionario' => ['view_production', 'add_production']
        ];
        
        $userPermissions = $permissions[$role] ?? [];
        
        return in_array('all', $userPermissions) || in_array($permission, $userPermissions);
    }
    
    /**
     * Verifica se o usuário é de um role específico
     */
    public function hasRole($role) {
        $user = $this->getUser();
        return $user && ($user['role'] ?? '') === $role;
    }
    
    /**
     * Requer que o usuário esteja logado
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . LOGIN_URL);
            exit;
        }
    }
    
    /**
     * Requer que o usuário tenha uma permissão específica
     */
    public function requirePermission($permission) {
        $this->requireLogin();
        
        if (!$this->hasPermission($permission)) {
            header('Location: acesso-bloqueado.php');
            exit;
        }
    }
    
    /**
     * Requer que o usuário seja de um role específico
     */
    public function requireRole($role) {
        $this->requireLogin();
        
        if (!$this->hasRole($role)) {
            header('Location: acesso-bloqueado.php');
            exit;
        }
    }
}
?>
