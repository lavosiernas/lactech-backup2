<?php
require_once 'config.php';
require_once 'database.php';

class RBAC {
    private static $instance = null;
    private $db;
    
    // Definição de permissões por role
    private $permissions = [
        'proprietario' => [
            'dashboard' => true,
            'gerenciar_usuarios' => true,
            'gerenciar_fazenda' => true,
            'relatorios_completos' => true,
            'configuracoes_sistema' => true,
            'produção' => true,
            'qualidade' => true,
            'financeiro' => true,
            'veterinario' => true,
            'funcionarios' => true,
            'gerente' => true
        ],
        'gerente' => [
            'dashboard' => true,
            'gerenciar_usuarios' => false,
            'gerenciar_fazenda' => false,
            'relatorios_completos' => true,
            'configuracoes_sistema' => false,
            'produção' => true,
            'qualidade' => true,
            'financeiro' => true,
            'veterinario' => true,
            'funcionarios' => true,
            'gerente' => false
        ],
        'veterinario' => [
            'dashboard' => true,
            'gerenciar_usuarios' => false,
            'gerenciar_fazenda' => false,
            'relatorios_completos' => false,
            'configuracoes_sistema' => false,
            'produção' => false,
            'qualidade' => true,
            'financeiro' => false,
            'veterinario' => true,
            'funcionarios' => false,
            'gerente' => false
        ],
        'funcionario' => [
            'dashboard' => true,
            'gerenciar_usuarios' => false,
            'gerenciar_fazenda' => false,
            'relatorios_completos' => false,
            'configuracoes_sistema' => false,
            'produção' => true,
            'qualidade' => true,
            'financeiro' => false,
            'veterinario' => false,
            'funcionarios' => false,
            'gerente' => false
        ]
    ];
    
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Verificar se usuário tem permissão
    public function hasPermission($role, $permission) {
        if (!isset($this->permissions[$role])) {
            return false;
        }
        
        return $this->permissions[$role][$permission] ?? false;
    }
    
    // Verificar se usuário pode acessar página
    public function canAccessPage($role, $page) {
        $pagePermissions = [
            'dashboard.php' => 'dashboard',
            'gerente.php' => 'gerente',
            'funcionario.php' => 'funcionarios',
            'veterinario.php' => 'veterinario',
            'proprietario.php' => 'gerenciar_fazenda',
            'alterar-senha.php' => 'dashboard',
            'configuracoes.php' => 'configuracoes_sistema'
        ];
        
        $requiredPermission = $pagePermissions[$page] ?? 'dashboard';
        return $this->hasPermission($role, $requiredPermission);
    }
    
    // Obter todas as permissões de um role
    public function getRolePermissions($role) {
        return $this->permissions[$role] ?? [];
    }
    
    // Obter páginas que o usuário pode acessar
    public function getAccessiblePages($role) {
        $pages = [
            'dashboard.php' => 'Dashboard',
            'gerente.php' => 'Painel do Gerente',
            'funcionario.php' => 'Painel do Funcionário',
            'veterinario.php' => 'Painel do Veterinário',
            'proprietario.php' => 'Painel do Proprietário',
            'alterar-senha.php' => 'Alterar Senha',
            'configuracoes.php' => 'Configurações'
        ];
        
        $accessiblePages = [];
        foreach ($pages as $page => $title) {
            if ($this->canAccessPage($role, $page)) {
                $accessiblePages[$page] = $title;
            }
        }
        
        return $accessiblePages;
    }
    
    // Middleware para verificar acesso
    public function requirePermission($permission) {
        if (!isset($_SESSION['user_role'])) {
            header('Location: ' . LOGIN_URL);
            exit;
        }
        
        if (!$this->hasPermission($_SESSION['user_role'], $permission)) {
            header('Location: acesso-bloqueado.php');
            exit;
        }
    }
    
    // Middleware para verificar acesso à página
    public function requirePageAccess($page) {
        if (!isset($_SESSION['user_role'])) {
            header('Location: ' . LOGIN_URL);
            exit;
        }
        
        if (!$this->canAccessPage($_SESSION['user_role'], $page)) {
            header('Location: acesso-bloqueado.php');
            exit;
        }
    }
    
    // Verificar se usuário é admin
    public function isAdmin($role) {
        return $role === 'proprietario';
    }
    
    // Verificar se usuário pode gerenciar outros usuários
    public function canManageUsers($role) {
        return $this->hasPermission($role, 'gerenciar_usuarios');
    }
    
    // Verificar se usuário pode ver relatórios completos
    public function canViewCompleteReports($role) {
        return $this->hasPermission($role, 'relatorios_completos');
    }
    
    // Obter hierarquia de roles
    public function getRoleHierarchy() {
        return [
            'proprietario' => 4,
            'gerente' => 3,
            'veterinario' => 2,
            'funcionario' => 1
        ];
    }
    
    // Verificar se role tem hierarquia superior
    public function hasHigherRole($role1, $role2) {
        $hierarchy = $this->getRoleHierarchy();
        $level1 = $hierarchy[$role1] ?? 0;
        $level2 = $hierarchy[$role2] ?? 0;
        
        return $level1 > $level2;
    }
}
?>
