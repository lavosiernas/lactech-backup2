<?php
/**
 * SafeNode - RateLimiter Unit Tests
 */

namespace SafeNode\Tests\Unit;

use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
    private $db;
    private $rateLimiter;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar mock do banco de dados
        $this->db = $this->createMock(\PDO::class);
        $this->rateLimiter = new \RateLimiter($this->db);
    }
    
    public function testCheckRateLimitWithoutDatabase()
    {
        $rateLimiter = new \RateLimiter(null);
        $result = $rateLimiter->checkRateLimit('192.168.1.1');
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals(999, $result['remaining']);
    }
    
    public function testCheckRateLimitWithEmptyLimits()
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([]);
        
        $this->db->method('query')->willReturn($stmt);
        
        $result = $this->rateLimiter->checkRateLimit('192.168.1.1');
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals(999, $result['remaining']);
    }
    
    public function testCheckRateLimitReturnsCorrectStructure()
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([]);
        
        $this->db->method('query')->willReturn($stmt);
        
        $result = $this->rateLimiter->checkRateLimit('192.168.1.1');
        
        $this->assertArrayHasKey('allowed', $result);
        $this->assertArrayHasKey('remaining', $result);
    }
}

