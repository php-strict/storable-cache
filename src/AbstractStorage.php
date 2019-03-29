<?php
/**
 * PHP Strict.
 * 
 * @copyright   Copyright (C) 2018 - 2019 Enikeishik <enikeishik@gmail.com>. All rights reserved.
 * @author      Enikeishik <enikeishik@gmail.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PhpStrict\StorableCache;

/**
 * Array-based pseudo storage.
 */
abstract class AbstractStorage implements StorageInterface
{
    /**
     * Fetches an item value from the cache.
     * 
     * @param string $key
     * 
     * @return string
     * 
     * @throws \PhpStrict\StorableCache\BadPacketException
     */
    public function getValue(string $key): string
    {
        return $this->getPacket($key)->getValue();
    }
    
    /**
     * Determines whether an item is present in the cache and life time expired.
     * 
     * @param string $key The unique cache key of the item to check for expiring.
     * 
     * @return bool True if the item was expired. False if not.
     * 
     * @throws \PhpStrict\StorableCache\BadPacketException
     */
    public function expired(string $key): bool
    {
        return $this->getPacket($key)->expired();
    }
    
    /**
     * Fetches an item value from the cache. Synonym for getValue method.
     * 
     * @param string $key
     * 
     * @return string
     * 
     * @throws \PhpStrict\StorableCache\BadPacketException
     */
    public function get(string $key): string
    {
        return $this->getValue($key);
    }
}
