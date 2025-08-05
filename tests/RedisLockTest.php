<?php

namespace AppTest;

use PHPUnit\Framework\Attributes\CoversClass;

define("SECOND", 1000);
define("MILLISECOND", 1000);

use App\Lock\RedisLock;
use PHPUnit\Framework\TestCase;

#[CoversClass("RedisLock")]
final class RedisLockTest extends TestCase
{
    public function test_successful_lock() {
        $resourceName = "test_successful_lock";
        $ttlMillis = 120 * SECOND;

        $redisMock = $this->createMock(\Redis::class);
        $redisMock->method("set")
            ->willReturn(true);
        
        $redlock = new RedisLock($redisMock);
        
        $actualResult = $redlock->lock($resourceName, $ttlMillis);
        $this->assertNotNull($actualResult, "Expected lock id (string). Got null.");
    }

    public function test_failed_lock() {
        $resourceName = "test_failed_lock";
        $ttlMillis = 120 * SECOND;

        $redisMock = $this->createMock(\Redis::class);
        $redisMock->method("set")
            ->willReturn(false);
        
        $redlock = new RedisLock($redisMock);
        
        $actualResult = $redlock->lock($resourceName, $ttlMillis);
        $this->assertNull($actualResult, "Expected lock id (string). Got null.");
    }

    public function test_successful_unlock() {
        $resourceName = "test_successful_unlock";
        $lockId = "lock_uuid";

        $redisMock = $this->createMock(\Redis::class);
        $redisMock->method("eval")
            ->willReturn(1);

        $redlock = new RedisLock($redisMock);
    
        $this->expectNotToPerformAssertions();
        try {
            $redlock->unlock($resourceName, $lockId);
        } catch (\Throwable $e) {
            $this->fail("Unable to release lock");
        } 
    }

    public function test_failed_unlock() {
        $resourceName = "test_failed_unlock";
        $lockId = "lock_uuid";

        $redisMock = $this->createMock(\Redis::class);
        $redisMock->method("eval")
            ->willThrowException(new \RedisException());

        $redlock = new RedisLock($redisMock);
    
        $this->expectErrorLog();
        $redlock->unlock($resourceName, $lockId); 
    }
}