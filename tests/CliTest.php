<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use LLegaz\Redis\Tools\RedisInspector;

$inspector = new RedisInspector();
$inspector->selectDatabase(12);
dump($inspector->getAllkeys());

//dump($inspector->getInfo());
dump($inspector->getTtl('tata'));

$inspector->dumpAllRedis();
