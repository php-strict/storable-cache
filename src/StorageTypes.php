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
 * StorableCacheTypes class.
 * 
 * Provides supported types of storages.
 */
class StorageTypes
{
    public const ST_ARRAY       = 'array';
    public const ST_FILES       = 'files';
    public const ST_MYSQL       = 'mysql';
    public const ST_SQLITE      = 'sqlite';
    public const ST_REDIS       = 'redis';
    public const ST_MEMCACHED   = 'memcached';
}
