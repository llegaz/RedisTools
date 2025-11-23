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
     * @WARNING BE AWARE THAT THE PAYLOAD RETURNED BY THIS METHOS IS NOT THE SAME 
     *          RATHER YOU USE PREDIS CLIENT OR THE PHP-REDIS ONE !
     * 
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

    /**
     * @param string $pool the pool's name
     * @return array
     * @throws ConnectionLostException
     */
    public function getPoolKeys(string $pool): array
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        return $this->getRedis()->hkeys($pool);
    }

    /**
     * @todo rework this
     *
     * @param string $key
     * @return int
     */
    public function getTtl(string $key): int
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        return $this->getRedis()->ttl($key);
    }

    /**
     * print all keys /values / TTL / count for each DB used in redis server instance 
     * 
     * 
     * @return array
     */
    public function dumpAllRedis(bool $silent = false): array
    {
        /**
         * 2 scenarios: either phpredis or predis
         */
        $info = $this->getInfo();
        $redis = $this->getRedis()->toString();
        $count = 0;
        $keys = [];

        for ($i = 0; $i < 16; $i++) {
            if ($redis === \LLegaz\Redis\PredisClient::PREDIS) {
                if (isset($info['Keyspace']['db' . $i])) {
                    $this->selectDatabase($i);

                    dd($info['Keyspace']['db' . $i]);
                    /*foreach ($poolNamesToDisplay as $poolName) {
                        $data = $cache->fetchAllFromPool($poolName);
                    }*/
                    // print values from DEFAULT POOL
                    //pU::colorGreenToCLI('DEFAULT pool data:');
                }
            } elseif (isset($info['db' . $i])) {
                $this->selectDatabase($i);
                $keys = $this->getAllkeys();
                $count = $this->getKeysCount($info['db' . $i]);
                //dd($info['db' . $i], $count, $keys);
                foreach ($keys as $key) {
                    dump($this->getPoolKeys($key));
                }
                
                
            }
        }
    }

    private function getKeysCount(string $payload) :int {
            $parts  = explode(',', $payload);

            foreach ($parts as $part) {
                $count = explode('=', $part);
                if (trim($count[0]) === "keys") {
                    return (int) trim($count[1]);
                }
            }

            return 0;
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
