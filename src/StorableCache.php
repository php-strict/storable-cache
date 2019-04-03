<?php
/**
 * PHP Strict.
 * 
 * @copyright   Copyright (C) 2018 - 2019 Enikeishik <enikeishik@gmail.com>. All rights reserved.
 * @author      Enikeishik <enikeishik@gmail.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace PhpStrict\StorableCache;

use PhpStrict\Config\Config;

/**
 * StorableCache class.
 * 
 * Storable cache store items more than their lifetime, up to time to save, 
 * and allow use stored items after expiration, in cases, for example, of db overload or errors.
 */
class StorableCache implements StorableCacheInterface
{
    /**
     * @var int
     */
    protected const LIFETIME = 3;
    
    /**
     * @var int
     */
    protected const SAVETIME = 3600;
    
    /**
     * @var \PhpStrict\StorableCache\StorageInterface
     */
    protected $storage;
    
    /**
     * Initializes storage for cache according to configuration settings.
     * 
     * @param \PhpStrict\Config\Config $config  configuration object
     * @param \mysqli $db = null                link to exists (and connected to DB) DB object
     * 
     * @throws \PhpStrict\StorableCache\StorageNotSupportedException
     * @throws \PhpStrict\StorableCache\StorageConnectException
     */
    public function __construct(Config $config, \mysqli $db = null)
    {
        $this->storage = $this->getStorage($config, $db);
    }
    
    /**
     * Creates and returns storage.
     * 
     * @param \PhpStrict\Config\Config $config  configuration object
     * @param \mysqli $db = null                link to exists (and connected to DB) DB object
     * 
     * @throws \PhpStrict\StorableCache\StorageNotSupportedException
     * @throws \PhpStrict\StorableCache\StorageConnectException
     * 
     * @return \PhpStrict\StorableCache\StorageInterface
     */
    protected function getStorage(Config $config, \mysqli $db = null): StorageInterface
    {
        switch ($config->cacheType) {
            
            case StorageTypes::ST_FILES:
                return new FilesStorage($config->cacheDir);
            
            case StorageTypes::ST_SQLITE:
                return new SqliteStorage($config->getSlice('cacheSqlite'));
            
            // @codeCoverageIgnoreStart
            case StorageTypes::ST_MEMCACHED:
                return new MemcachedStorage(
                    $config->cacheMemcachedHost ?? null, 
                    $config->cacheMemcachedPort ?? null
                );
            // @codeCoverageIgnoreEnd
            
            case StorageTypes::ST_REDIS:
                return new RedisStorage(
                    $config->cacheRedisHost ?? null, 
                    $config->cacheRedisPort ?? null
                );
            
            case StorageTypes::ST_MYSQL:
                return new MysqlStorage($db, $config->getSlice('cacheMysql'));
            
            case StorageTypes::ST_ARRAY:
                return new ArrayStorage();
            
        }
        
        throw new StorageNotSupportedException();
    }
    
    /**
     * Determines whether an item is present in the cache.
     * 
     * @param string $key
     * 
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->storage->has($key);
    }
    
    /**
     * Fetches a value from the cache.
     * 
     * @param string $key       The unique key of this item in the cache.
     * @param string $default   Default value to return if the key does not exist.
     * 
     * @return string The value of the item from the cache, or $default in case of cache miss.
     */
    public function get(string $key, string $default = ''): string
    {
        try {
            return $this->storage->get($key) ?? $default;
        } catch (BadPacketException $e) {
            return $default;
        }
    }
    
    /**
     * Persists data in the cache, uniquely referenced by a key with an optional lifetime and time to save.
     * 
     * @param string $key   The key of the item to store.
     * @param string $value The value of the item to store.
     * @param int $lifetime Optional. The lifetime value of this item. If no value is sent 
     *                      then the library must set a default value for it.
     * @param int $savetime Optional. The time to save value of this item. If no value is sent 
     *                      then the library must set a default value for it.
     * 
     * @return bool True on success and false on failure.
     */
    public function set(string $key, string $value, int $lifetime = null, int $savetime = null): bool
    {
        if (null === $lifetime || 0 > $lifetime) {
            $lifetime = static::LIFETIME;
        }
        if (null === $savetime || 0 > $savetime) {
            $savetime = static::SAVETIME;
        }
        
        return $this->storage->set($key, $value, $lifetime, $savetime);
    }
    
    /**
     * Delete an item from the cache by its unique key.
     * 
     * @param string $key The unique cache key of the item to delete.
     * 
     * @return bool True if the item was successfully removed. False if there was an error.
     */
    public function delete(string $key): bool
    {
        return $this->storage->delete($key);
    }
    
    /**
     * Wipes clean the entire cache's keys.
     * 
     * @return bool True on success and false on failure.
     */
    public function clear(): bool
    {
        return $this->storage->clear();
    }
    
    /**
     * Determines whether an item is present in the cache and life time expired.
     * 
     * @param string $key The unique cache key of the item to check for expiring.
     * 
     * @return bool True if the item was expired. False if not.
     */
    public function expired(string $key): bool
    {
        try {
            return $this->storage->expired($key);
        } catch (BadPacketException $e) {
            return true;
        }
    }
    
    /**
     * Deletes all outdated (time to save expired) cache items in a single operation.
     * 
     * @return bool True on success and false on failure.
     */
    public function deleteOutdated(): bool
    {
        return $this->storage->deleteOutdated();
    }
}
