<?php
use \PhpStrict\Config\Config as AbstractConfig;
use \PhpStrict\StorableCache\BadPacketException;
use \PhpStrict\StorableCache\Packet;
use \PhpStrict\StorableCache\StorableCache;
use \PhpStrict\StorableCache\StorageConnectException;
use \PhpStrict\StorableCache\StorageTypes;

class Config extends AbstractConfig
{
    //place configuration here
}

class StorableCacheTest extends \Codeception\Test\Unit
{
    /**
     * @param string $expectedExceptionClass
     * @param callable $call = null
     */
    protected function expectedException(string $expectedExceptionClass, callable $call = null)
    {
        try {
            $call();
        } catch (\Exception $e) {
            $this->assertEquals($expectedExceptionClass, get_class($e));
            return;
        }
        if ('' != $expectedExceptionClass) {
            $this->fail('Expected exception not throwed');
        }
    }
    
    // tests
    protected function testCacheCases($cache, $deleteOutdated = true)
    {
        $cache->clear();
        
        $this->assertEmpty($cache->get('any-key'));
        $this->assertEquals('default-value', $cache->get('any-key', 'default-value'));
        
        $this->assertTrue($cache->set('any-key', 'any-value'));
        $this->assertTrue($cache->set('any-key', 'any-value')); //add the same
        $this->assertTrue($cache->set('any-key2', 'any-value2', 2));
        $this->assertTrue($cache->set('any-key3', 'any-value3', 3, 30));
        
        $this->assertTrue($cache->has('any-key'));
        $this->assertEquals('any-value', $cache->get('any-key'));
        $this->assertEquals('any-value', $cache->get('any-key', 'default-value'));
        
        $this->assertTrue($cache->delete('any-key'));
        $this->assertEmpty($cache->get('any-key'));
        
        $cache->set('any-key', 'any-value');
        $this->assertTrue($cache->clear());
        $this->assertEmpty($cache->get('any-key'));
        
        $this->assertTrue($cache->expired('key-not-exists'));
        $cache->set('key1', 'value1', 1, 1);
        $cache->set('key2', 'value2', 3, 30);
        $this->assertFalse($cache->expired('key1'));
        sleep(2);
        $this->assertTrue($cache->expired('key1'));
        $this->assertFalse($cache->expired('key2'));
        
        if ($deleteOutdated) {
            $this->assertTrue($cache->deleteOutdated());
            $this->assertFalse($cache->has('key1'));
            $this->assertTrue($cache->has('key2'));
        }
        
        $cache->clear();
    }
    
    protected function testCacheCasesFail($cache)
    {
        $this->assertEmpty($cache->get('any-key'));
        $this->assertEquals('default-value', $cache->get('any-key', 'default-value'));
        
        $this->assertFalse($cache->set('any-key', 'any-value'));
        $this->assertFalse($cache->has('any-key'));
        $this->assertEquals('', $cache->get('any-key'));
        $this->assertEquals('default-value', $cache->get('any-key', 'default-value'));
        
        $this->assertFalse($cache->delete('any-key'));
        $this->assertFalse($cache->clear());
        
        $this->assertTrue($cache->expired('key-not-exists'));
        $this->assertFalse($cache->deleteOutdated());
    }
    
    /**
     * @group array
     */
    public function testCacheArrayStorage()
    {
        $config = new Config();
        $config->cacheType = StorageTypes::ST_ARRAY;
        $cache = new StorableCache($config);
        
        $this->assertEmpty($cache->get('any-key'));
        $this->assertEquals('default-value', $cache->get('any-key', 'default-value'));
        $this->assertTrue($cache->set('any-key', 'any-value'));
        $this->assertTrue($cache->delete('any-key'));
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->has('any-key'));
        $this->assertTrue($cache->set('key1', 'value1'));
        $this->assertFalse($cache->has('key1'));
        $this->assertTrue($cache->expired('key1'));
        $this->assertTrue($cache->deleteOutdated());
        
        $cache = new class($config) extends StorableCache {
            public $storage;
        };
        $this->expectedException(
            \PhpStrict\StorableCache\BadPacketException::class, 
            function() use($cache) { $cache->storage->getPacket('any-key'); }
        );
        $this->expectedException(
            \PhpStrict\StorableCache\BadPacketException::class, 
            function() use($cache) { $cache->storage->getValue('any-key'); }
        );
    }
    
    /**
     * @group packet 
     */
    public function testCachePacket()
    {
        $packet = new Packet('value', 1, 10);
        $this->assertEquals('value', $packet->getValue());
        $this->assertEquals(time(), $packet->getTimestamp());
        $this->assertEquals(1, $packet->getLifetime());
        $this->assertEquals(10, $packet->getSavetime());
        
        $packet = new Packet('value', 1, 10, 1000);
        $this->assertEquals(1000, $packet->getTimestamp());
    }
    
    /**
     * @group files
     */
    public function testCacheFilesStorage()
    {
        if (strcasecmp(substr(PHP_OS, 0, 3), 'WIN') === 0) {
            $cacheDir = 'c:/tmp/ufo-cache-test-dir';
            $badCacheDir = 'c:/tmp/unexistence-ufo-cache-test-dir';
        } else {
            $cacheDir = '/tmp/ufo-cache-test-dir';
            $badCacheDir = '/tmp/unexistence-ufo-cache-test-dir';
        }
        if (file_exists($cacheDir)) {
            if (is_dir($cacheDir)) {
                $this->rrmdir($cacheDir);
            } else {
                unlink($cacheDir);
            }
        }
        mkdir($cacheDir);
        
        $config = new Config();
        $config->cacheType = StorageTypes::ST_FILES;
        $config->cacheDir = $cacheDir;
        $cache = new StorableCache($config);
        
        $this->testCacheCases($cache);
        
        $this->assertFalse($cache->has(''));
        $this->assertFalse($cache->has('asd@123.qwe!zxc#rty$456'));
        
        file_put_contents($cacheDir . '/bad-packet', '');
        $this->assertEquals('', $cache->get('bad-packet'));
        $this->assertEquals('default-value', $cache->get('bad-packet', 'default-value'));
        $this->assertTrue($cache->deleteOutdated());
        
        rmdir($cacheDir);
        
        $config->cacheDir = '';
        $this->expectedException(
            \PhpStrict\StorableCache\StorageConnectException::class, 
            function() use($config) { $cache = new StorableCache($config); }
        );
        
        $config->cacheDir = $badCacheDir;
        $this->expectedException(
            \PhpStrict\StorableCache\StorageConnectException::class, 
            function() use($config) { $cache = new StorableCache($config); }
        );
        
        mkdir($badCacheDir);
        $cache = new StorableCache($config);
        rmdir($badCacheDir);
        $this->testCacheCasesFail($cache);
    }
    
    /**
     * @group memcache
     */
    public function testCacheMemcachedStorage()
    {
        //disable tests if extension not enabled (on travis-ci)
        if (!class_exists('\Memcache')) {
            return;
        }
        $config = new Config();
        $config->cacheType = StorageTypes::ST_MEMCACHED;
        $config->cacheMemcachedHost = 'localhost';
        $config->cacheMemcachedPort = 11211;
        try {
            $cache = new StorableCache($config);
        } catch (StorageConnectException $e) {
            //disable tests if memcached service is not running
            return;
        }
        $this->testCacheCases($cache, false);
        $this->assertTrue($cache->deleteOutdated(0));
    }
    
    /**
     * @group redis
     */
    public function testCacheRedisStorage()
    {
        //disable tests if extension not enabled
        if (!class_exists('\Redis')) {
            return;
        }
        $config = new Config();
        $config->cacheType = StorageTypes::ST_REDIS;
        $config->cacheRedisHost = 'localhost';
        $config->cacheRedisPort = 6379;
        try {
            $cache = new StorableCache($config);
        } catch (StorageConnectException $e) {
            //disable tests if redis service is not running
            return;
        }
        $this->testCacheCases($cache);
        
        $r = new \Redis();
        $r->connect($config->cacheRedisHost, $config->cacheRedisPort);
        $r->set('bad-packet', '', 10);
        $r->close();
        $this->assertTrue($cache->deleteOutdated());
        
        $config = new Config();
        $config->cacheType = StorageTypes::ST_REDIS;
        $config->cacheRedisHost = 'localhost';
        $config->cacheRedisPort = 16379;
        $this->expectedException(
            \PhpStrict\StorableCache\StorageConnectException::class, 
            function() use($config) { $cache = new StorableCache($config); }
        );
    }
    
    /**
     * @group sqlite
     */
    public function testCacheSqliteStorage()
    {
        $config = new Config();
        $config->cacheType = StorageTypes::ST_SQLITE;
        $config->cacheSqliteBase = dirname(__DIR__) . '/_data/cache.db';
        $cache = new StorableCache($config);
        $this->testCacheCases($cache);
        
        $config->cacheSqliteTable = 'non_exists_field';
        $config->cacheSqliteKeyField = 'non_exists_field';
        $config->cacheSqliteValueField = 'non_exists_field';
        $config->cacheSqliteTimestampField = 'non_exists_field';
        $config->cacheSqliteLifetimeField = 'non_exists_field';
        $config->cacheSqliteSavetimeField = 'non_exists_field';
        $cache = new StorableCache($config);
        $this->testCacheCasesFail($cache);
    }
    
    /**
     * @group mysql
     */
    public function testCacheMysqlStorage()
    {
        $config = new Config();
        try {
            $config->loadFromIni(dirname(__DIR__) . '/_data/mysql.cfg', true);
        } catch (PhpStrict\Config\FileNotExistsException $e) {
            //disable tests if mysql config is not present
            return;
        }
        $config->cacheType = StorageTypes::ST_MYSQL;
        $mysqli = new \mysqli($config->dbHost, $config->dbUser, $config->dbPassword, $config->dbName);
        
        $cache = new StorableCache($config, $mysqli);
        $this->testCacheCases($cache);
        
        $config->cacheMysqlTable = 'non_exists_field';
        $config->cacheMysqlKeyField = 'non_exists_field';
        $config->cacheMysqlValueField = 'non_exists_field';
        $config->cacheMysqlTimestampField = 'non_exists_field';
        $config->cacheMysqlLifetimeField = 'non_exists_field';
        $config->cacheMysqlSavetimeField = 'non_exists_field';
        $cache = new StorableCache($config, $mysqli);
        $this->testCacheCasesFail($cache);
    }
    
    public function testCacheUnsupportedStorage()
    {
        $config = new Config();
        $config->cacheType = '';
        $this->expectedException(
            \PhpStrict\StorableCache\StorageNotSupportedException::class, 
            function() use($config) { $cache = new StorableCache($config); }
        );
    }
    
    protected function rrmdir($src)
    {
        if (!file_exists($src)) {
            return;
        }
        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    rrmdir($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }
}
