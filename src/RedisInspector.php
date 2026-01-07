<?php

declare(strict_types=1);

namespace LLegaz\Redis\Tools;

use League\CLImate\CLImate;
use LLegaz\Redis\PredisClient;
use LLegaz\Redis\RedisAdapter;
use LLegaz\Redis\RedisClient;
use LLegaz\Redis\RedisClientInterface;

/**
 * Redis Inspector - A debugging tool for analyzing Redis cache structures
 *
 *
 * <b>WARNING: DO NOT USE IN PRODUCTION</b>
 *
 * This tool uses Redis commands with O(n) complexity (KEYS, HGETALL) which can
 * significantly impact Redis performance. Use only in development/debugging scenarios.
 *
 * Designed to work with PSR-6 (Cache Pools) and PSR-16 (Simple Cache) implementations
 * from my other repositories.
 * @see https://github.com/llegaz/RedisCache PSR-6 and PSR-16 implementation for Redis
 *
 *
 *
 * <b>WARNING: DO NOT USE IN PRODUCTION</b>
 *
 * This is a first naive version, it could be enhanced a lot of course and hopefully it will be.
 * Contributions are welcomed.
 *
 *
 * @see https://github.com/llegaz/RedisCache PSR-6 and PSR-16 implementation for Redis
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class RedisInspector extends RedisAdapter implements InspectorInterface
{
    private const DEFAULT_POOL_NAME = 'DEFAULT_Cache_Pool';
    private const CACHE_LABEL = 'SimpleCache PSR-16';
    private const DEFAULT_DB_COUNT = 16;

    private const TTL_FOREVER = -1;
    private const TTL_NOT_FOUND = -2;

    private CLImate $cli;

    public function __construct(
        string $host = RedisClientInterface::DEFAULTS['host'],
        int $port = RedisClientInterface::DEFAULTS['port'],
        ?string $pwd = null,
        string $scheme = RedisClientInterface::DEFAULTS['scheme'],
        int $db = RedisClientInterface::DEFAULTS['database'],
        bool $persistent = false,
    ) {
        parent::__construct($host, $port, $pwd, $scheme, $db, $persistent);
        $this->cli = new CLImate();
    }

    /**
     * Dumps the SimpleCache (PSR-16) store for the currently selected database
     * @caution you have to select the redis database prior the call
     * @see RedisAdapter::selectDatabase
     *
     * @param bool $silent Whether to suppress CLI output
     * @return array Key-value pairs from the SimpleCache store
     * @throws ConnectionLostException|\Exception
     */
    public function dumpCacheStore(bool $silent = true): array
    {
        $currentDb = $this->getContext()['database'];
        $allData = $this->dumpAllRedis($silent, $currentDb, $currentDb + 1);

        // Extract cache store data for current DB
        return $allData["db{$currentDb}"][self::CACHE_LABEL] ?? [];
    }

    /**
     * Dumps all SimpleCache (PSR-16) stores across all databases
     *
     *
     * @param bool $silent Whether to suppress CLI output
     * @return array Associative array of cache stores by database
     * @throws ConnectionLostException|\Exception
     */
    public function dumpAllCacheStores(bool $silent = true): array
    {
        $allData = $this->dumpAllRedis($silent);
        $cacheStores = [];

        foreach ($allData as $dbName => $dbData) {
            if (isset($dbData[self::CACHE_LABEL])) {
                $cacheStores[$dbName] = $dbData[self::CACHE_LABEL];
            }
        }

        return $cacheStores;
    }

    /**
     * Retrieves Redis server information
     *
     *
     * @return array Server information (format varies by client: Predis vs PhpRedis)
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
     * Gets all keys (hash fields) from a specific cache pool
     *
     *
     * @param string $pool The pool name to inspect
     * @return array List of keys in the pool
     * @throws ConnectionLostException
     */
    public function getPoolKeys(string $pool = self::DEFAULT_POOL_NAME): array
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        $keys = $this->getRedis()->hkeys($pool);

        return is_array($keys) ? $keys : [];
    }

    /**
     * Gets the TTL (Time To Live) for a Redis key
     *
     *
     * @param string $key The Redis key
     * @return int TTL in seconds, -1 if no expiry, -2 if key doesn't exist
     * @throws ConnectionLostException
     */
    public function getTtl(string $key): int
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        return $this->getRedis()->ttl($key);
    }

    /**
     * Dumps all Redis data across specified database range
     *
     *
     * @param bool $silent Whether to suppress CLI output
     * @param int $db_start Starting database index
     * @param int $db_end Ending database index (exclusive)
     * @return array Complete dump of all data organized by database
     * @throws ConnectionLostException|\Exception
     */
    public function dumpAllRedis(
        bool $silent = false,
        int $db_start = 0,
        int $db_end = self::DEFAULT_DB_COUNT
    ): array {
        $this->validateDatabaseRange($db_start, $db_end);

        $client = $this->getRedis()->toString();
        $this->displayIntro($client, $silent);

        return match ($client) {
            PredisClient::PREDIS => $this->dumpAllPredis($silent, $db_start, $db_end),
            RedisClient::PHP_REDIS => $this->dumpAllPhpRedis($silent, $db_start, $db_end),
            default => throw new \Exception("Unsupported Redis client: {$client}")
        };
    }

    /**
     * Dumps a specific cache pool with all its keys, values, and TTLs
     * @caution you have to select the redis database prior the call
     * @see RedisAdapter::selectDatabase
     *
     *
     * @param string $pool Pool name to dump
     * @param bool $silent Whether to suppress CLI output
     * @return array Pool data as structured array
     * @throws ConnectionLostException|\Exception
     */
    public function dumpCachePool(
        string $pool = self::DEFAULT_POOL_NAME,
        bool $silent = false
    ): array {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        $poolData = $this->getRedis()->hgetall($pool);
        if (!$this->isPool($poolData)) {
            if (!$silent) {
                $this->cli->red()->bold()->out("Pool '{$pool}' is empty or doesn't exist.");
            }

            return [];
        }

        $result = $this->formatPoolData($pool, $poolData);
        if (!$silent) {
            $this->displayPoolData($pool, $result);
        }

        return $result;
    }

    /**
     * Dumps only the keys from a specific cache pool
     * @caution you have to select the redis database prior the call
     * @see RedisAdapter::selectDatabase
     *
     * @param string $pool Pool name
     * @param bool $silent Whether to suppress CLI output
     * @return array List of keys in the pool
     * @throws ConnectionLostException
     */
    public function dumpCachePoolKeys(
        string $pool = self::DEFAULT_POOL_NAME,
        bool $silent = false
    ): array {
        $keys = $this->getPoolKeys($pool);

        if (!$silent) {
            $this->displayPoolKeys($pool, $keys);
        }

        return $keys;
    }

    /**
     * Gets all keys from the currently selected database
     * @caution you have to select the redis database prior the call
     * @see RedisAdapter::selectDatabase
     *
     * <b>WARNING:</b> Uses KEYS command - O(n) complexity, blocking operation!
     *
     * @return array All keys in current database
     * @throws ConnectionLostException
     */
    public function getAllkeys(): array
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        return $this->getRedis()->keys('*');
    }

    // ========== PRIVATE HELPER METHODS ==========

    /**
     * validation logic
     *
     * @throws \InvalidArgumentException
     */
    private function validateDatabaseRange(int $start, int $end): void
    {
        if ($start < 0 || $end < 0) {
            throw new \InvalidArgumentException('Database indices must be non-negative');
        }

        if ($start >= $end) {
            throw new \InvalidArgumentException('Start database must be less than end database');
        }
    }

    /**
     *
     * @param string $client
     * @param bool $silent
     * @return void
     */
    private function displayIntro(string $client, bool $silent): void
    {
        if ($silent) {
            return;
        }

        $this->cli->inline('>' . PHP_EOL . '> Dumping Redis server data using ');
        $this->cli->underline()->inline($client)->out(' client.' . PHP_EOL);
        $this->cli->backgroundLightBlue()->black()
            ->out('For each DB: PSR-6 pools first, then PSR-16 SimpleCache.');
        $this->cli->out(PHP_EOL);
    }

    /**
     *
     *
     * @param bool $silent
     * @param int $db_start
     * @param int $db_end
     * @return array
     * @throws ConnectionLostException|\Exception
     */
    private function dumpAllPhpRedis(bool $silent, int $db_start, int $db_end): array
    {
        return $this->dumpAllDatabases($silent, $db_start, $db_end, RedisClientInterface::PHP_REDIS);
    }

    /**
     *
     * @param bool $silent
     * @param int $db_start
     * @param int $db_end
     * @return array
     * @throws ConnectionLostException|\Exception
     */
    private function dumpAllPredis(bool $silent, int $db_start, int $db_end): array
    {
        return $this->dumpAllDatabases($silent, $db_start, $db_end, RedisClientInterface::PREDIS);
    }

    /**
     *
     * @param bool $silent
     * @param int $db_start
     * @param int $db_end
     * @param string $client 'phpredis' or 'predis'
     * @return array
     * @throws ConnectionLostException|\Exception
     */
    private function dumpAllDatabases(
        bool $silent,
        int $db_start,
        int $db_end,
        string $client
    ): array {
        $info = $this->getInfo();
        $result = [];

        for ($dbIndex = $db_start; $dbIndex < $db_end; $dbIndex++) {
            $dbName = "db{$dbIndex}";

            // Check if database exists (different structure for each client)
            if (!$this->databaseExists($info, $dbName, $client)) {
                continue;
            }

            $this->selectDatabase($dbIndex);
            $keys = $this->getAllkeys();
            $keyCount = $this->getDatabaseKeyCount($info, $dbName, $client);

            // Validate key count matches
            if (count($keys) !== $keyCount) {
                throw new \Exception("Key count mismatch for {$dbName}: expected {$keyCount}, got " . count($keys));
            }

            if (!$silent) {
                $this->displayDatabaseHeader($dbName, $keyCount);
            }

            // Process all keys in this database
            $dbData = $this->processDatabaseKeys($keys, $dbName, $silent, $client);

            $result[$dbName] = [
                'count' => $keyCount,
                ...$dbData['pools'],
                self::CACHE_LABEL => $dbData['cache']
            ];

            if (!$silent && $dbData['poolCount'] > 0) {
                $plural = $dbData['poolCount'] > 1 ? 's' : '';
                $this->cli->yellow()->bold()
                    ->out("Iterated on {$dbData['poolCount']} PSR-6 pool{$plural}" . PHP_EOL);
            }

            if (!$silent) {
                $this->displayCacheStore($dbName, $dbData['cache']);
            }
        }

        return $result;
    }

    /**
     * If the database exists then it should be returned by redis server info
     *
     * @param array $info
     * @param string $dbName
     * @param string $client
     * @return bool
     */
    private function databaseExists(array $info, string $dbName, string $client): bool
    {
        if ($client === RedisClientInterface::PREDIS) {
            return isset($info['Keyspace'][$dbName]);
        }

        return isset($info[$dbName]);
    }

    /**
     *
     * @param array $info
     * @param string $dbName
     * @param string $client
     * @return int
     */
    private function getDatabaseKeyCount(array $info, string $dbName, string $client): int
    {
        if ($client === RedisClientInterface::PREDIS) {
            return (int) ($info['Keyspace'][$dbName]['keys'] ?? 0);
        }

        return $this->parseKeysCount($info[$dbName] ?? '');
    }

    /**
     *
     * @param string $payload
     * @return int
     */
    private function parseKeysCount(string $payload): int
    {
        $parts = explode(',', $payload);

        foreach ($parts as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, '0');

            if (trim($key) === 'keys') {
                return (int) trim($value);
            }
        }

        return 0;
    }

    /**
     *
     * @param array $keys
     * @param string $dbName
     * @param bool $silent
     * @param string $client
     * @return array
     */
    private function processDatabaseKeys(
        array $keys,
        string $dbName,
        bool $silent,
        string $client
    ): array {
        $pools = [];
        $cacheEntries = [];
        $poolCount = 0;

        foreach ($keys as $key) {
            $poolData = $this->fetchPoolData($key, $client);

            if ($this->isPool($poolData)) {
                // PSR-6 Pool (Hash)
                $poolCount++;
                $formattedPool = $this->formatPoolData($key, $poolData);
                $pools[$key] = $formattedPool;

                if (!$silent) {
                    $this->displayPoolData($dbName, $formattedPool, $key);
                }
            } else {
                // PSR-16 Simple Cache (String)
                $cacheEntries[] = $this->formatCacheEntry($key);
            }
        }

        return [
            'pools' => $pools,
            'cache' => $cacheEntries,
            'poolCount' => $poolCount
        ];
    }

    /**
     *
     * @param string $key
     * @param string $client
     * @return array|null
     */
    private function fetchPoolData(string $key, string $client): ?array
    {
        try {
            $data = $this->getRedis()->hgetall($key);

            return is_array($data) ? $data : null;
        } catch (\Exception $e) {
            // Predis may throw exceptions for non-hash keys
            return null;
        }
    }

    /**
     *
     * @param array|null $data
     * @return bool
     */
    private function isPool(?array $data): bool
    {
        return is_array($data) && !empty($data);
    }

    /**
     *
     * @param string $poolName
     * @param array $poolData
     * @return array
     */
    private function formatPoolData(string $poolName, array $poolData): array
    {
        $formatted = [];
        $ttl = $this->getTtl($poolName);
        $ttlDisplay = $this->formatTtl($ttl);

        foreach ($poolData as $hashKey => $hashValue) {
            $formatted[] = [
                "{$poolName} pool key" => $hashKey,
                "{$poolName} pool value" => $hashValue,
                'TTL' => $ttlDisplay
            ];
        }

        return $formatted;
    }

    /**
     *
     * @param string $key
     * @return array
     */
    private function formatCacheEntry(string $key): array
    {
        $ttl = $this->getTtl($key);
        $value = $this->getRedis()->get($key);

        return [
            self::CACHE_LABEL . ' key' => $key,
            self::CACHE_LABEL . ' value' => $value ?? 'empty or error',
            'TTL' => $this->formatTtl($ttl)
        ];
    }

    /**
     *
     * @param int $ttl
     * @return string
     */
    private function formatTtl(int $ttl): string
    {
        return match ($ttl) {
            self::TTL_FOREVER => 'forever',
            self::TTL_NOT_FOUND => 'key not found',
            default => $ttl . 's'
        };
    }

    /**
     *
     * @param string $dbName
     * @param int $count
     * @return void
     */
    private function displayDatabaseHeader(string $dbName, int $count): void
    {
        $plural = $count > 1 ? 's' : '';

        $this->cli->backgroundLightYellow()->blink()->dim()->black()->inline('> > > >');
        $this->cli->yellow()->bold()->inline(" {$dbName}")
            ->yellow()->inline(" ({$count} key{$plural}) ");
        $this->cli->backgroundLightYellow()->blink()->dim()->black()->out('< < < <');
    }

    /**
     *
     * @param string $context
     * @param array $data
     * @param string|null $poolName
     * @return void
     */
    private function displayPoolData(string $context, array $data, ?string $poolName = null): void
    {
        if (empty($data)) {
            return;
        }

        $displayName = $poolName ?? $context;

        if ($poolName) {
            $this->cli->yellow()->inline($context)
                ->yellow()->inline(' - ')
                ->yellow()->out($poolName);
        } else {
            $this->cli->yellow()->bold()->out($displayName);
        }

        $this->cli->yellow()->table($data);
    }

    /**
     *
     * @param string $dbName
     * @param array $cacheData
     * @return void
     */
    private function displayCacheStore(string $dbName, array $cacheData): void
    {
        $count = count($cacheData);

        if ($count) {
            $this->cli->yellow()->inline($dbName)
                ->yellow()->inline(' - ')
                ->yellow()->underline()->inline(self::CACHE_LABEL)
                ->yellow()->bold()->out(" ({$count} keys)");

            $this->cli->cyan()->table($cacheData);
        }
    }

    /**
     *
     * @param string $poolName
     * @param array $keys
     * @return void
     */
    private function displayPoolKeys(string $poolName, array $keys): void
    {
        $count = count($keys);
        $plural = $count > 1 ? 's' : '';

        $this->cli->backgroundLightYellow()->blink()->dim()->black()->inline('> > > >');
        $this->cli->yellow()->bold()->inline(" {$poolName}")
            ->yellow()->inline(" ({$count} key{$plural}) ");
        $this->cli->backgroundLightYellow()->blink()->dim()->black()->out('< < < <');

        foreach ($keys as $key) {
            $this->cli->yellow()->out($key);
        }
    }
}
