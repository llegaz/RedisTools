<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use LLegaz\Redis\Tools\RedisInspector;

$inspector = new RedisInspector();

/*$db=255;
if ($inspector->selectDatabase($db) === false) {
    dump("cannot connect to db-" . $db);
}*/

$inspector->selectDatabase(12);


//dump($inspector->getAllkeys());

//dump($inspector->getInfo());
//dump($inspector->getTtl('tata'));




//dump($inspector->dumpCacheStore());
dump($inspector->dumpAllCacheStores());

//$inspector->dumpAllRedis();
