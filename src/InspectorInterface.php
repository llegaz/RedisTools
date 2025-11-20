<?php


namespace LLegaz\Redis\Tools;

/**
 * OK there is a lot to rework here
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
     * print or return as string all cache info, for a given poolname. (Key, value and TTL)
     * 
     * 
     * Default Cache pool if $pool parameter is null
     * 
     * if $silent parameter is false then print on STD_OUT probably have to Handle OB things 
     * (@todo handle CLI colors too check CLI mate ?)
     */
    public function printCachePool(string $pool = null, bool $silent = false): ?string;

    /**
     * @todo rework this
     *
     * print only pool keys set (Hash Keys)
     * 
     * it uses getPoolKeys
     * 
     *
     * @return null
     * @throws ConnectionLostException
     */
    public function printCachePoolKeys(string $pool = null, bool $silent = false): ?string;


    /**
     * @param string $pool the pool's name
     * @return array
     * @throws ConnectionLostException
     */
    public function getPoolKeys(string $pool): array;

    /**
     * @todo rework this
     *
     * @return array
     * @throws ConnectionLostException
     */
    public function getInfo(): array;

    /**
     * @todo rework this
     *
     * @param string $key
     * @return int
     */
    public function getTtl(string $key): int;

    /**
     * @todo rework this
     *
     * key => value array is returned corresponding accurately to the redis cache set (the PSR-16 SimpleCache only)
     *
     * @return array
     * @throws ConnectionLostException
     */
    public function getAllCacheStoreAsArray();

    /**
     *
     * print everything in Cache Store for the selected Database (the PSR-16 SimpleCache only)
     * (except HSET entries)
     * select DB prior call
     *
     * @return string
     * @throws ConnectionLostException
     */
    public function getAllCacheStoreAsString(): string;

    /**
     * print the entire REDIS data concerning PSR-16 cache and PSR-6 pools cache
     * 
     * array by DB 1 to 16 
     * 
     * then by pools' names (maybe find a default name for the SimpleCache)
     * 
     * @return array
     */
    public function printAllRedis(): array;

    /**
     *
     * disclaimer: <b>DO NOT USE EXECPT IN DEBUGGING SCENARIO</b> this redis call is too intensive in O(n) complexity
     * so the more keys the more blocking it is for all redis clients trying to access the redis db
     *
     * @return array all the keys in redis (for a selected db ?)
     */
    public function getAllkeys(): array;
}