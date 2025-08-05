<?php
require_once dirname(__DIR__) . "/vendor/autoload.php";

use App\Lock\RedisLock;

define("SECOND", 1000);
define("MILLISECOND", 1000);

$redlock = RedisLock::default();

$optinos = getopt("", ["value:", "sleep-time:"]);
$value = $optinos["value"] ?? null;
$sleepTime = $optinos["sleep-time"] ?? null;
if ($value === null || $sleepTime === null)
    exit(1);

$value = intval($value);
$sleepTime = intval($sleepTime);
echo "Started #$value" . PHP_EOL;
$redlock->runBlocking("main", function() use ($value, $sleepTime): void {
    $startTime = microtime(true);
    sleep($sleepTime);
    $executionTime = number_format(microtime(true) - $startTime, 3) * MILLISECOND;
    echo "Hello #$value. Execution time: ${executionTime}ms" . PHP_EOL;
});