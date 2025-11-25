<?php

declare(strict_types=1);

namespace LLegaz\Redis\Tools;

/**
 *
 * KEEP IN MIND :
 *
 *    - Hash aren't same type as STRING
 *
 *    - SimpleCache PSR-16 is based on STRING SET (Key / value)
 *    - while Cache Pools PSR-6 are base on Hash key (poolname) + field (key) + value
 *
 *
 *
 *
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
interface InspectorInterface
{
    /**
     * print or return as array all cache pool fields, for a given pool's name. (key, value and TTL)
     *
     *
     * Default Cache pool if $pool parameter is null
     *
     * if $silent parameter is false then all data are printed directly using STD_OUT (terminal)
     */
    public function dumpCachePool(string $pool = null, bool $silent = false): array;

    /**
     * print only pool keys set (Hash Keys) for the currently selected db only.
     *
     *
     * @param string $pool
     * @param bool $silent
     * @return array
     * @throws ConnectionLostException
     */
    public function dumpCachePoolKeys(string $pool = null, bool $silent = false): array;


    /**
     * for the currently selected db only.
     *
     * @param string $pool the pool's name
     * @return array
     * @throws ConnectionLostException
     */
    public function getPoolKeys(string $pool): array;

    /**
     * returns Redis server info
     * (either a flat array in case of PhpRedis or a more sophisticated array if predis is used instead)
     *
     * @return array
     * @throws ConnectionLostException
     */
    public function getInfo(): array;

    /**
     * Return the TTL associated to a key (a entire pool is a key.. see notes in llegaz/redis-cache)
     *
     * @param string $key
     * @return int
     * @throws ConnectionLostException
     */
    public function getTtl(string $key): int;

    /**
     * An "key => value" array is returned corresponding accurately to the redis cache set
     * (the PSR-16 SimpleCache only) for the currently selected db only.
     *
     *
     * @param bool $silent
     * @return array
     * @throws ConnectionLostException | \Exception
     */
    public function dumpCacheStore(bool $silent = false): array;

    /**
     * Basically dumpCacheStore method applied to all databases set (16 by default)
     * and not only to the currently selected db.
     *
     *
     * @param bool $silent
     * @return array
     * @throws ConnectionLostException | \Exception
     */
    public function dumpAllCacheStores(bool $silent = false): array;

    /**
     *
     * print everything in Cache Store for the selected Database (the PSR-16 SimpleCache only)
     * (except HSET entries)
     * select DB prior call
     *
     * @return string
     * @throws ConnectionLostException
     */

    /**
     * print the entire REDIS data concerning PSR-16 cache and PSR-6 pools cache
     *
     * array by DB 1 to 16
     *
     * then by pools' names (maybe find a default name for the SimpleCache)
     *
     * @return array
     */
    public function dumpAllRedis(bool $silent = false, int $db_start, int $db_end): array;

    /**
     *
     * disclaimer: <b>DO NOT USE EXECPT IN DEBUGGING SCENARIO</b> this redis call is too intensive in O(n) complexity
     * so the more keys the more blocking it is for all redis clients trying to access the redis db
     *
     * @return array all the keys in redis (for a selected db ?)
     */
    public function getAllkeys(): array;
}
