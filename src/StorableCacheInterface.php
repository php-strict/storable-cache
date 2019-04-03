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
 * Storable cache interface.
 * 
 * Storable cache store items more than their lifetime, up to time to save, 
 * and allow use stored items after expiration, in cases, for example, of db overload or errors.
 */
interface StorableCacheInterface
{
    /**
     * Determines whether an item is present in the cache.
     * 
     * @param string $key
     * 
     * @return bool
     */
    public function has(string $key): bool;
    
    /**
     * Fetches a value from the cache.
     * 
     * @param string $key       The unique key of this item in the cache.
     * @param string $default   Default value to return if the key does not exist.
     * 
     * @return string The value of the item from the cache, or $default in case of cache miss.
     */
    public function get(string $key, string $default = ''): string;
    
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
    public function set(string $key, string $value, int $lifetime = 0, int $savetime = 0): bool;
    
    /**
     * Delete an item from the cache by its unique key.
     * 
     * @param string $key The unique cache key of the item to delete.
     * 
     * @return bool True if the item was successfully removed. False if there was an error.
     */
    public function delete(string $key): bool;
    
    /**
     * Wipes clean the entire cache's keys.
     * 
     * @return bool True on success and false on failure.
     */
    public function clear(): bool;
    
    /**
     * Determines whether an item is present in the cache and life time expired.
     * 
     * @param string $key The unique cache key of the item to check for expiring.
     * 
     * @return bool True if the item was expired. False if not.
     */
    public function expired(string $key): bool;
    
    /**
     * Deletes all outdated (time to save expired) cache items in a single operation.
     * 
     * @return bool True on success and false on failure.
     */
    public function deleteOutdated(): bool;
}
