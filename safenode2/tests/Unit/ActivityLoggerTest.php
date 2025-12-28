<?php
/**
 * SafeNode - ActivityLogger Unit Tests
 */

namespace SafeNode\Tests\Unit;

use PHPUnit\Framework\TestCase;

class ActivityLoggerTest extends TestCase
{
    private $db;
    private $activityLogger;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar mock do banco de dados
        $this->db = $this->createMock(\PDO::class);
        $this->activityLogger = new \ActivityLogger($this->db);
    }
    
    public function testLogActivityWithValidData()
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        
        $this->db->method('prepare')->willReturn($stmt);
        
        // Mock $_SERVER para getClientIP
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        
        $result = $this->activityLogger->log(1, 'test_action', 'Test description');
        
        $this->assertTrue($result);
    }
    
    public function testLogActivityWithMetadata()
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        
        $this->db->method('prepare')->willReturn($stmt);
        
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        
        $metadata = ['key' => 'value', 'test' => true];
        $result = $this->activityLogger->log(1, 'test_action', 'Test', 'success', $metadata);
        
        $this->assertTrue($result);
    }
}

