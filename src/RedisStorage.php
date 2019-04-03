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

/**
 * Cache storage based on Redis service.
 */
class RedisStorage extends AbstractStorage
{
    /**
     * @var \Redis
     */
    protected $db = null;
    
    /**
     * @var string
     */
    protected $host = 'localhost';
    
    /**
     * @var int
     */
    protected $port = 6379;
    
    /**
     * Initialising connection to Redis server.
     * 
     * @param string $host = null   Redis server host
     * @param int $port = null      Redis server port
     * 
     * @throws \PhpStrict\Cache\CacheStorageNotSupportedException
     * @throws \PhpStrict\Cache\CacheStorageConnectException
     */
    public function __construct(string $host = null, int $port = null)
    {
        if (!class_exists('\Redis')) {
            throw new StorageNotSupportedException(); // @codeCoverageIgnore
        }
        
        if (null !== $host) {
            $this->host = $host;
        }
        if (null !== $port) {
            $this->port = $port;
        }
        
        $this->db = new \Redis();
        try {
            if (false === $this->db->connect($this->host, $this->port)) {
                throw new StorageConnectException();
            }
        } catch (\Throwable $e) {
            throw new StorageConnectException();
        }
    }
    
    public function __destruct()
    {
        if (null !== $this->db) {
            $this->db->close();
        }
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
        return 0 !== $this->db->exists($key);
    }
    
    /**
     * Fetches an item (packet) from the cache.
     * 
     * @param string $key
     * 
     * @return \PhpStrict\StorableCache\Packet
     * 
     * @throws \PhpStrict\StorableCache\BadPacketException
     */
    public function getPacket(string $key): Packet
    {
        $packet = unserialize((string) $this->db->get($key));
        if (false === $packet || !($packet instanceof Packet)) {
            throw new BadPacketException();
        }
        return $packet;
    }
    
    /**
     * Persists data in the cache, uniquely referenced by a key with an optional lifetime and time to save.
     * 
     * @param string $key   The key of the item to store.
     * @param string $value The value of the item to store.
     * @param int $lifetime The lifetime value of this item.
     *                      The library must store items with expired lifetime.
     * @param int $savetime The time to save value of this item. 
     *                      The library can delete outdated items automatically with expired savetime.
     * 
     * @return bool True on success and false on failure.
     */
    public function set(string $key, string $value, int $lifetime, int $savetime): bool
    {
        return $this->db->set(
            $key, 
            serialize(new Packet($value, $lifetime, $savetime)), 
            $savetime
        );
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
        return 0 !== $this->db->del($key);
    }
    
    /**
     * Deletes all outdated (time to save expired) cache items in a single operation.
     * 
     * @return bool True on success and false on failure.
     */
    public function deleteOutdated(): bool
    {
        $keys = $this->db->keys('*');
        foreach ($keys as $key) {
            try {
                $packet = $this->getPacket($key);
                if ($packet->outdated()) {
                    $this->delete($key);
                }
            } catch (BadPacketException $e) {
                $this->delete($key);
            }
        }
        return true;
    }
    
    /**
     * Wipes clean the entire cache's keys.
     * 
     * @return bool
     */
    public function clear(): bool
    {
        return $this->db->flushall();
    }
}
