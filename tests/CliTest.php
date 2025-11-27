<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use LLegaz\Redis\Tools\RedisInspector;

$inspector = new RedisInspector();

/*$db=255;
if ($inspector->selectDatabase($db) === false) {
    dump("cannot connect to db-" . $db);
}*/

/*$inspector->selectDatabase(12);


dump($inspector->getAllkeys());

dump($inspector->getInfo());
dump($inspector->getTtl('tata'));

$inspector->dumpCachePool();
$inspector->dumpCachePoolKeys();
dump($inspector->getPoolKeys());



dump($inspector->dumpCacheStore());

dump($inspector->dumpAllCacheStores());





$inspector->selectDatabase(1);
$inspector->dumpCachePoolKeys('test');
dump($inspector->dumpCachePoolKeys('test', true));
$inspector->dumpCachePool('test');


$inspector->dumpAllRedis();
*/

$inspector->dumpAllRedis(false, 0, 2);


echo $inspector->getRedis() . PHP_EOL;
