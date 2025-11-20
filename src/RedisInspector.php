<?php

declare(strict_types=1);

namespace LLegaz\Redis\Tools;

/**
 * 
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class RedisInspector extends \LLegaz\Redis\RedisAdapter implements InspectorInterface
{
    public function getAllCacheStoreAsArray(): array {
        
    }

    public function getAllCacheStoreAsString(): string {
        
    }

    public function getAllkeys(): array {
        
    }

    public function getInfo(): array {
        
    }

    public function getPoolKeys(string $pool): array {
        
    }

    public function getTtl(string $key): int {
        
    }

    public function printAllRedis(): array {
        
    }

    public function printCachePool(string $pool = null, bool $silent = false): ?string {
        
    }

    public function printCachePoolKeys(string $pool = null, bool $silent = false): ?string {
        
    }

}