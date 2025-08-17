<?php

namespace App\Tests\Entity;

use App\Entity\Sessions;
use PHPUnit\Framework\TestCase;

class SessionsTest extends TestCase
{
    public function testCreateSession(): void
    {
        $session = new Sessions();
        $this->assertInstanceOf(Sessions::class, $session);
    }

    public function testSessionGettersAndSetters(): void
    {
        $session = new Sessions();
        $userId = 1;
        $ipAddress = "192.168.1.1";
        $userAgent = "Mozilla/5.0 Chrome/91.0";
        $payload = "session_data_here";
        $lastActivity = time();

        $session->setUserId($userId);
        $session->setIpAddress($ipAddress);
        $session->setUserAgent($userAgent);
        $session->setPayload($payload);
        $session->setLastActivity($lastActivity);

        $this->assertEquals($userId, $session->getUserId());
        $this->assertEquals($ipAddress, $session->getIpAddress());
        $this->assertEquals($userAgent, $session->getUserAgent());
        $this->assertEquals($payload, $session->getPayload());
        $this->assertEquals($lastActivity, $session->getLastActivity());
    }

    public function testSessionNullableFields(): void
    {
        $session = new Sessions();
        
        $this->assertNull($session->getUserId());
        $this->assertNull($session->getIpAddress());
        $this->assertNull($session->getUserAgent());
        
        $session->setUserId(null);
        $session->setIpAddress(null);
        $session->setUserAgent(null);
        
        $this->assertNull($session->getUserId());
        $this->assertNull($session->getIpAddress());
        $this->assertNull($session->getUserAgent());
    }

    public function testSessionFluentInterface(): void
    {
        $session = new Sessions();
        
        $result = $session->setUserId(1)
                         ->setIpAddress("127.0.0.1")
                         ->setPayload("test_payload")
                         ->setLastActivity(time());
        
        $this->assertSame($session, $result);
    }
}
