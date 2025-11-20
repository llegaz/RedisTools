<?php

declare(strict_types=1);

namespace LLegaz\Redis\Tools;

/**
 * !!!!
 * <b>WARNING: DO NOT USE THIS PACKAGE EXECPT IN DEBUGGING SCENARIO</b>
 * !!!!
 *
 *
 * the redis calls used here are too cpu / memory intensive with an O(n) complexity !
 *
 * => so the more keys the more blocking it will be for all redis clients trying to
 *    access the redis db...
 *
 *
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class RedisInspector extends \LLegaz\Redis\RedisAdapter implements InspectorInterface
{
    public const CACHE = 'SimpleCachePSR-16';
    public const DB_COUNT = 16;

    public function getAllCacheStoreAsArray(): array
    {
        
    }

    public function getAllCacheStoreAsString(): string
    {

    }

    /**
     * @todo rework this
     *
     * @return array
     * @throws ConnectionLostException
     */
    public function getInfo(): array
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        return $this->getRedis()->info();
    }

    public function getPoolKeys(string $pool): array
    {

    }

    public function getTtl(string $key): int
    {

    }

    public function printAllRedis(): array
    {

    }

    public function printCachePool(string $pool = null, bool $silent = false): ?string
    {

    }

    public function printCachePoolKeys(string $pool = null, bool $silent = false): ?string
    {

    }


    /**
     *
     * disclaimer: <b>DO NOT USE EXECPT IN DEBUGGING SCENARIO</b> this redis call is too intensive in O(n) complexity
     * so the more keys the more blocking it is for all redis clients trying to access the redis db
     *
     * it returns all the keys of the current DB, that is to say SimpleCache (PSR-16) Keys AND all pools' names (Hashes Keys)
     *
     *
     * @return array all the keys in redis for the <b>currently selected db</b>
     */
    public function getAllkeys(): array
    {
        return $this->getRedis()->keys('*');
    }

}
