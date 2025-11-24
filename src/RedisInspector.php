<?php

declare(strict_types=1);

namespace LLegaz\Redis\Tools;

use League\CLImate\CLImate;
use LLegaz\Redis\PredisClient;
use LLegaz\Redis\RedisAdapter;
use LLegaz\Redis\RedisClient;
use LLegaz\Redis\RedisClientInterface;
use Psr\Log\LoggerInterface;

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
class RedisInspector extends RedisAdapter implements InspectorInterface
{
    /**
     * display and settings
     */
    public const CACHE = 'SimpleCachePSR-16';
    public const DB_COUNT = 16;
    private CLImate $cli;

    public function __construct(
        string $host = RedisClientInterface::DEFAULTS['host'],
        int $port = RedisClientInterface::DEFAULTS['port'],
        ?string $pwd = null,
        string $scheme = RedisClientInterface::DEFAULTS['scheme'],
        int $db = RedisClientInterface::DEFAULTS['database'],
        bool $persistent = false,
        ?RedisClientInterface $client = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($host, $port, $pwd, $scheme, $db, $persistent, $client, $logger);
        $this->cli = new CLImate();
    }
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
        $keys = $this->getRedis()->hkeys($pool);
        if (is_array($keys)) {

            return $keys;
        }

        return [];
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
        if (!$silent) {
            $this->cli->inline('Dumping Redis server data using ');
        }
        if ($this->getRedis()->toString() === PredisClient::PREDIS) {
            if (!$silent) {
                $this->cli->underline()->inline('Predis')->out(' client.');
            }

            return $this->dumpAllPredis($silent);
        } elseif ($this->getRedis()->toString() === RedisClient::PHP_REDIS) {
            if (!$silent) {
                $this->cli->underline()->inline('PhpRedis')->out(' client.');
            }

            return $this->dumpAllPhpRedis($silent);
        }

        $this->throwUEx();
    }

    private function dumpAllPhpRedis(bool $silent = false): array
    {
        $info = $this->getInfo();
        $count = 0;
        $keys = [];
        $toReturn = [];
        $dbName = '';

        /***
         * we parse all dbs, each DB owns a data store (PSR-16 SimpleCache) and possibly many pools
         */
        for ($i = 0; $i < self::DB_COUNT; $i++) {
            $dbName = 'db' . $i;
            if (isset($info[$dbName])) {
                $this->selectDatabase($i);
                $keys = $this->getAllkeys();
                $count = $this->getKeysCount($info[$dbName]);

                if (count($keys) !== $count) {
                    $this->throwUEx("keys\' count should be the same");
                }
                if ($silent) {
                    // populate toReturn array with data
                    $toReturn[$dbName]['count'] = $count;
                    $toReturn[$dbName]['keys'] = [];
                } else {
                    $this->cli->yellow()->out($dbName)->out('keys count=' . $count);
                }
                foreach ($keys as $key) {
                    $hkeys = $this->getPoolKeys($key);
                    if (count($hkeys)) {
                        dump('for the pool named: ' . $key);
                        foreach ($hkeys as $hkey) {
                            dump($key . ':' . $hkey . '=' . $this->getRedis()->hget($key, $hkey) . ' - ' . $this->getTtl($key));
                        }
                    } else {
                        $this->cli->green()->out(self::CACHE); // STR Cache case
                    }

                }


            }
        }

        return $toReturn;
    }

    private function dumpAllPredis(bool $silent = false): array
    {
        $toReturn = [];

        $info = $this->getInfo();
        $count = 0;
        $keys = [];
        $toReturn = [];

        for ($i = 0; $i < self::DB_COUNT; $i++) {
            if (isset($info['Keyspace']['db' . $i])) {
                $this->selectDatabase($i);
                $keys = $this->getAllkeys();
                dd($keys);
                $count = (int) $info['Keyspace']['db' . $i]['keys'] ?? $count;
                //dd($info['Keyspace']['db' . $i], $count);
                if (count($keys) !== $count) {
                    $this->throwUEx("keys\' count should be the same");
                }
                foreach ($keys as $key) {
                    $hkeys = $this->getPoolKeys($key);
                    if (count($hkeys)) {
                        dump('for the pool named: ' . $key);
                        foreach ($hkeys as $hkey) {
                            dump($key . ':' . $hkey . '=' . $this->getRedis()->hget($key, $hkey) . ' - ' . $this->getTtl($key));
                        }
                    }

                }


            }
        }

        return $toReturn;
    }

    private function getKeysCount(string $payload): int
    {
        $parts  = explode(',', $payload);

        foreach ($parts as $part) {
            $count = explode('=', $part);
            if (trim($count[0]) === 'keys') {
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
