<?php
define("MILLISECOND", 1000);

class RedisLock
{
    private Redis $redisInstance;

    public function __construct(array $redisConfig)
    {
        $this->redisInstance = new Redis();
        $this->redisInstance->connect(
            $redisConfig["host"],
            $redisConfig["port"],
            $redisConfig["timeout"],
        );
    }

    public function lock(string $resource, int $ttlMillis): ?string
    {
        $lock_uuid = uniqid(more_entropy: true);
        $startTime = microtime(true);

        $isSet = false;
        try {
            $isSet = $this->redisInstance->set($resource, $lock_uuid, ["NX", "PX" => $ttlMillis]);
        } catch (RedisException $e) {
            error_log("Unable to aquire lock. Cause: " . $e->getMessage() . PHP_EOL);
        }

        $elapsedTimeMillis = (microtime(true) - $startTime) * MILLISECOND;
        if ($isSet === true && $elapsedTimeMillis < $ttlMillis) {
            return $lock_uuid;
        } else {
            $this->unlock($resource, $lock_uuid);
            return null;
        }
    }

    public function unlock(string $resource, string $lock_uuid): void
    {
        try {
            $unlockScript = "if redis.call('get', KEYS[1]) == ARGV[1] then return redis.call('del', KEYS[1]) else return 0 end";
            $this->redisInstance->eval($unlockScript, [$resource, $lock_uuid], 1);
        } catch (RedisException $e) {
            error_log("Unable to release lock. Cause: " . $e->getMessage() . PHP_EOL);
        }
    }

    public function runBlocking(string $resource, callable $criticalSection): void
    {
        $shouldRun = true;
        while ($shouldRun) {
            if ($lock_id = $this->lock("main", 1200 * 1000)) {
                
                $criticalSection();

                $this->unlock("main", $lock_id);
                $shouldRun = false;
            } else {
                usleep(10 * MILLISECOND);
            }
        }
    }
}

echo "Started";

$redlock = new RedisLock([
    "host" => "localhost",
    "port" => "6379",
    "timeout" => "0.5",
]);

$optinos = getopt("", ["value:"]);
$value = intval($optinos["value"]) ?? null;
if ($value === null)
    exit(1);

$redlock->runBlocking("main", function() use ($value): void {
    sleep(2);
    echo "Hello #$value";
});