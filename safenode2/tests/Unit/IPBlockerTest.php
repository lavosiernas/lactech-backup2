<?php
/**
 * SafeNode - IPBlocker Unit Tests
 */

namespace SafeNode\Tests\Unit;

use PHPUnit\Framework\TestCase;

class IPBlockerTest extends TestCase
{
    private $db;
    private $ipBlocker;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar mock do banco de dados
        $this->db = $this->createMock(\PDO::class);
        $this->ipBlocker = new \IPBlocker($this->db);
    }
    
    public function testIsBlockedWithoutDatabase()
    {
        $ipBlocker = new \IPBlocker(null);
        $result = $ipBlocker->isBlocked('192.168.1.1');
        
        $this->assertFalse($result);
    }
    
    public function testIsWhitelistedWithoutDatabase()
    {
        $ipBlocker = new \IPBlocker(null);
        $result = $ipBlocker->isWhitelisted('192.168.1.1');
        
        $this->assertFalse($result);
    }
    
    public function testIsBlockedWithBlockedIP()
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute');
        $stmt->method('fetch')->willReturn(['id' => 1, 'ip_address' => '192.168.1.1']);
        
        $this->db->method('prepare')->willReturn($stmt);
        
        $result = $this->ipBlocker->isBlocked('192.168.1.1');
        
        $this->assertTrue($result);
    }
    
    public function testIsBlockedWithNonBlockedIP()
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute');
        $stmt->method('fetch')->willReturn(false);
        
        $this->db->method('prepare')->willReturn($stmt);
        
        $result = $this->ipBlocker->isBlocked('192.168.1.1');
        
        $this->assertFalse($result);
    }
    
    public function testBlockIPWithWhitelistedIP()
    {
        // Mock para isWhitelisted retornar true
        $whitelistStmt = $this->createMock(\PDOStatement::class);
        $whitelistStmt->method('execute');
        $whitelistStmt->method('fetch')->willReturn(['id' => 1]);
        
        $this->db->method('prepare')->willReturn($whitelistStmt);
        
        $result = $this->ipBlocker->blockIP('192.168.1.1', 'Test reason');
        
        $this->assertFalse($result);
    }
}

