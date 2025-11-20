<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use LLegaz\Redis\Tools\RedisInspector;

$inspector = new RedisInspector();
dump($inspector->getAllkeys());
