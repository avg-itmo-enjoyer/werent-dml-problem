<?php

namespace App\Lock;

class RedisLock
{
    public function __construct(
        private \Redis $redisInstance,
    ) {}

    public static function default(): static 
    {
        return static::fromConfig([
            "host" => "localhost",
            "port" => "6379",
            "timeout" => "0.5", 
        ]);
    }

    public static function fromConfig(array $redisConfig): static 
    {
        $configuredRedisInstanse = new \Redis();
        if (!$configuredRedisInstanse->connect(
            $redisConfig["host"] ?? null,
            $redisConfig["port"] ?? null,
            $redisConfig["timeout"] ?? null,
        )) throw new \RedisException("Unable to connect to Redis");
        return new static($configuredRedisInstanse);
    }

    public function lock(string $resource, int $ttlMillis): ?string
    {
        $lockId = uniqid(more_entropy: true);
        $startTime = microtime(true);

        $isSet = false;
        try {
            $isSet = $this->redisInstance->set($resource, $lockId, ["NX", "PX" => $ttlMillis]);
        } catch (\RedisException $e) {
            error_log("Unable to aquire lock. Cause: " . $e->getMessage() . PHP_EOL);
        }

        $elapsedTimeMillis = (microtime(true) - $startTime) * MILLISECOND;
        if ($isSet === true && $elapsedTimeMillis < $ttlMillis) {
            return $lockId;
        } else {
            $this->unlock($resource, $lockId);
            return null;
        }
    }

    public function unlock(string $resource, string $lockId): void
    {
        try {
            $unlockScript = "if redis.call('get', KEYS[1]) == ARGV[1] then return redis.call('del', KEYS[1]) else return 0 end";
            $this->redisInstance->eval($unlockScript, [$resource, $lockId], 1);
        } catch (\RedisException $e) {
            error_log("Unable to release lock. Cause: " . $e->getMessage() . PHP_EOL);
        }
    }

    public function runBlocking(string $resource, callable $criticalSection): void
    {
        $shouldRun = true;
        while ($shouldRun) {
            if ($lockId = $this->lock($resource, 120 * SECOND)) {
                
                $criticalSection();

                $this->unlock($resource, $lockId);
                $shouldRun = false;
            } else {
                usleep(10 * MILLISECOND);
            }
        }
    }
}