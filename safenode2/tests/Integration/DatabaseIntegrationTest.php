<?php
/**
 * SafeNode - Database Integration Tests
 * 
 * NOTA: Estes testes requerem um banco de dados de teste configurado
 * Configure as variáveis de ambiente de teste antes de executar
 */

namespace SafeNode\Tests\Integration;

use PHPUnit\Framework\TestCase;

class DatabaseIntegrationTest extends TestCase
{
    private $db;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar conexão de teste
        // Em produção, use variáveis de ambiente
        $host = getenv('TEST_DB_HOST') ?: 'localhost';
        $dbname = getenv('TEST_DB_NAME') ?: 'safenode_test';
        $username = getenv('TEST_DB_USER') ?: 'root';
        $password = getenv('TEST_DB_PASS') ?: '';
        
        try {
            // Primeiro, conectar sem especificar o banco para criar se não existir
            $pdo = new \PDO(
                "mysql:host={$host};charset=utf8mb4",
                $username,
                $password,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]
            );
            
            // Criar banco de dados se não existir
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Agora conectar ao banco específico
            $this->db = new \PDO(
                "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
                $username,
                $password,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]
            );
            
            // Criar tabelas necessárias para os testes
            $this->createTestTables();
        } catch (\PDOException $e) {
            $this->markTestSkipped('Banco de dados de teste não disponível: ' . $e->getMessage());
        }
    }
    
    /**
     * Cria as tabelas necessárias para os testes
     */
    private function createTestTables(): void
    {
        // Tabela de rate limits
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS safenode_rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                time_window INT NOT NULL,
                max_requests INT NOT NULL,
                priority INT DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabela de security logs
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS safenode_security_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                threat_type VARCHAR(50),
                action_taken VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip (ip_address),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabela de blocked IPs
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS safenode_blocked_ips (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL UNIQUE,
                reason TEXT,
                threat_type VARCHAR(50),
                expires_at TIMESTAMP NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip (ip_address)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabela de whitelist
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS safenode_whitelist (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL UNIQUE,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabela de activity log
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS safenode_activity_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                description TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                device_type VARCHAR(50),
                browser VARCHAR(100),
                os VARCHAR(100),
                metadata TEXT,
                status VARCHAR(50) DEFAULT 'success',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabela de rate limit violations
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS safenode_rate_limits_violations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                rate_limit_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip (ip_address)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    public function testDatabaseConnection()
    {
        $this->assertInstanceOf(\PDO::class, $this->db);
    }
    
    public function testRateLimiterIntegration()
    {
        $rateLimiter = new \RateLimiter($this->db);
        $result = $rateLimiter->checkRateLimit('127.0.0.1');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('allowed', $result);
        $this->assertArrayHasKey('remaining', $result);
    }
    
    public function testIPBlockerIntegration()
    {
        $ipBlocker = new \IPBlocker($this->db);
        $result = $ipBlocker->isBlocked('127.0.0.1');
        
        $this->assertIsBool($result);
    }
    
    public function testActivityLoggerIntegration()
    {
        $activityLogger = new \ActivityLogger($this->db);
        
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        
        $result = $activityLogger->log(1, 'test', 'Integration test');
        
        $this->assertTrue($result);
    }
}

