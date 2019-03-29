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
 * Files-based storage.
 */
class FilesStorage extends AbstractStorage
{
    /**
     * @var string
     */
    protected $cacheDir = '/cache';
    
    /**
     * Takes and check $cacheDirPath
     * 
     * @param string $cacheDirPath path to directory there cache entries will be stored
     * 
     * @throws \PhpStrict\StorableCache\StorageConnectException
     */
    public function __construct(string $cacheDirPath)
    {
        $this->cacheDir = $cacheDirPath;
        
        if (!is_dir($this->cacheDir) || !is_writable($this->cacheDir)) {
            throw new StorageConnectException('Cache dir not exists or not writable');
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
        return file_exists($this->getCashFilePath($key));
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
        return $this->getPacketFromFile($this->getCashFilePath($key));
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
        $cacheFile = $this->getCashFilePath($key);
        
        if (!$handle = @fopen($cacheFile, 'w')) {
            return false;
        }
        
        $written = false;
        
        if (flock($handle, LOCK_EX | LOCK_NB)) {
            fwrite($handle, serialize(new Packet($value, $lifetime, $savetime)));
            fflush($handle);
            flock($handle, LOCK_UN);
            $written = true;
        }
        
        fclose($handle);
        
        return $written;
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
        return @unlink($this->getCashFilePath($key));
    }
    
    /**
     * Deletes all outdated (time to save expired) cache items in a single operation.
     * 
     * @return bool True on success and false on failure.
     */
    public function deleteOutdated(): bool
    {
        //TODO: check the need to call
        clearstatcache();
        
        $dh = @opendir($this->cacheDir);
        if (false === $dh) {
            return false;
        }
        
        while (false !== ($entry = readdir($dh))) {
            $filePath = $this->cacheDir . '/' . $entry;
            
            //exclude '.', '..', '.htaccess'
            if (!is_file($filePath) || 0 === strpos($entry, '.')) {
                continue;
            }
            
            try {
                $packet = $this->getPacketFromFile($filePath);
                if ($packet->outdated()) {
                    unlink($filePath);
                }
            } catch (BadPacketException $e) {
                unlink($filePath);
            }
        }
        
        closedir($dh);
        
        return true;
    }
    
    /**
     * Wipes clean the entire cache's keys.
     * 
     * @return bool
     */
    public function clear(): bool
    {
        //TODO: check the need to call
        clearstatcache();
        
        $dh = @opendir($this->cacheDir);
        if (false === $dh) {
            return false;
        }
        
        while (false !== ($entry = readdir($dh))) {
            $file = $this->cacheDir . '/' . $entry;
            if (is_file($file) && 0 !== strpos($entry, '.')) { //exclude .htaccess
                unlink($file);
            }
        }
        
        closedir($dh);
        
        return true;
    }
    
    /**
     * @param string $filePath
     * 
     * @return \PhpStrict\StorableCache\Packet
     * 
     * @throws \PhpStrict\StorableCache\BadPacketException
     */
    protected function getPacketFromFile(string $filePath): Packet
    {
        if (!is_readable($filePath)) {
            throw new BadPacketException('File not readable');
        }
        $packet = unserialize(file_get_contents($filePath));
        if (!($packet instanceof Packet)) {
            throw new BadPacketException('Unserialize failed');
        }
        return $packet;
    }
    
    /**
     * @param string $key
     * 
     * @return string
     */
    protected function getCashFilePath(string $key): string
    {
        return $this->cacheDir . '/' . $this->getHash($key);
    }
    
    /**
     * @param string $key
     * 
     * @return string
     */
    protected function getHash(string $key): string
    {
        if ('' == $key) {
            return 'empty,' . time();
        } elseif (preg_match('/[^A-Za-z0-9~_,\.\/\-]|(\.{2})/', $key)) {
            return md5($key);
        }
        return str_replace('/', ',', $key);
    }
}
