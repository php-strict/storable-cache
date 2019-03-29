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
 * Cache storage based on project MySQL database.
 */
class MysqlStorage extends AbstractStorage
{
    /**
     * @var \mysqli
     */
    protected $db = null;
    
    /**
     * @var string
     */
    protected $table = 'cache';
    
    /**
     * @var string
     */
    protected $keyField = 'key';
    
    /**
     * @var string
     */
    protected $valueField = 'value';
    
    /**
     * @var string
     */
    protected $timestampField = 'created';
    
    /**
     * @var string
     */
    protected $lifetimeField = 'lifetime';
    
    /**
     * @var string
     */
    protected $savetimeField = 'savetime';
    
    /**
     * Initialising connection to SQLite server.
     * 
     * @param \mysqli $db       link to exists (and connected to DB) DB object
     * @param object $settings  DB connection and other settings
     * 
     * @throws \PhpStrict\StorableCache\StorageConnectException
     */
    public function __construct(\mysqli $db, object $settings)
    {
        $this->db = $db;
        
        if (isset($settings->table)) {
            $this->table = $settings->table;
        }
        if (isset($settings->keyField)) {
            $this->keyField = $settings->keyField;
        }
        if (isset($settings->valueField)) {
            $this->valueField = $settings->valueField;
        }
        if (isset($settings->timestampField)) {
            $this->timestampField = $settings->timestampField;
        }
        if (isset($settings->lifetimeField)) {
            $this->lifetimeField = $settings->lifetimeField;
        }
        if (isset($settings->savetimeField)) {
            $this->savetimeField = $settings->savetimeField;
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
        $sql =  'SELECT COUNT(*) AS Cnt FROM #__' . $this->table
                . " WHERE `" . $this->keyField . "`='" . $this->db->real_escape_string($key) . "'";
        
        $result = $this->db->query($sql);
        if (!$result) {
            return false;
        }
        
        $row = $result->fetch_assoc();
        $result->free();
        
        if (is_array($row) && array_key_exists('Cnt', $row)) {
            return 0 < (int) $row['Cnt'];
        }
        
        return false;
    }
    
    /**
     * Fetches an item (packet) from the cache.
     * 
     * @param string $key
     * 
     * @return \Ufo\StorableCache\Packet
     * 
     * @throws \Ufo\StorableCache\BadPacketException
     */
    public function getPacket(string $key): Packet
    {
        $sql =  'SELECT '
                . '`' . $this->valueField . '`, `' . $this->timestampField . '`,'
                . '`' . $this->lifetimeField . '`, `' . $this->savetimeField . '`'
                . ' FROM `' . $this->table . '`'
                . ' WHERE `' . $this->keyField . '`=' . "'" . $this->db->real_escape_string($key) . "'";
        
        $result = $this->db->query($sql);
        if (!$result) {
            throw new BadPacketException('Query failed');
        }
        
        try {
            $row = $result->fetch_assoc();
            $result->free();
            
            return new Packet(
                $row[$this->valueField], 
                $row[$this->lifetimeField], 
                $row[$this->savetimeField], 
                $row[$this->timestampField]
            );
        } catch (\Throwable $e) {
            throw new BadPacketException($e->getMessage(), $e->getCode(), $e);
        }
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
        if (!$this->has($key)) {
            $sql =  'INSERT INTO #__' . $this->table
                    . '('
                    . '`' . $this->keyField . '`, '
                    . '`' . $this->valueField . '`, '
                    . '`' . $this->timestampField . '`,'
                    . '`' . $this->lifetimeField . '`,'
                    . '`' . $this->savetimeField . '`'
                    . ')'
                    . ' VALUES('
                    . "'" . $this->db->real_escape_string($key) . "',"
                    . "'" . $this->db->real_escape_string($value) . "',"
                    . "'" . time() . "',"
                    . "'" . $lifetime . "',"
                    . "'" . $savetime . "'"
                    . ')';
        } else {
            $sql =  'UPDATE #__' . $this->table
                    . ' SET '
                    . '`' . $this->valueField . '`=' . "'" . $this->db->real_escape_string($value) . "',"
                    . '`' . $this->timestampField . '`=' . "'" . time() . "',"
                    . '`' . $this->lifetimeField . '`=' . "'" . $lifetime . "',"
                    . '`' . $this->savetimeField . '`=' . "'" . $savetime . "'"
                    . ' WHERE `' . $this->keyField . '`='
                    . "'" . $this->db->real_escape_string($key) . "'";
        }
        return $this->db->query($sql);
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
        $sql =  'DELETE FROM #__' . $this->table
                . " WHERE `" . $this->keyField
                . "`='" . $this->db->real_escape_string($key) . "'";
        return $this->db->query($sql);
    }
    
    /**
     * Deletes all outdated (time to save expired) cache items in a single operation.
     * 
     * @return bool True on success and false on failure.
     */
    public function deleteOutdated(): bool
    {
        $sql =  'DELETE FROM `' . $this->table . '`'
                . ' WHERE `' . $this->savetimeField . '`<'
                . '(' . time() . '-`' . $this->timestampField . '`)';
        return $this->db->query($sql);
    }
    
    /**
     * Wipes clean the entire cache's keys.
     * 
     * @return bool
     */
    public function clear(): bool
    {
        $sql =  'TRUNCATE #__' . $this->table;
        return $this->db->query($sql);
    }
}
