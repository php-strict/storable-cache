# Storable Cache

[![Software License][ico-license]](LICENSE.txt)

Storable cache store items more than their lifetime, up to time to save, 
and allow use stored items after expiration, in cases, for example, of db overload or errors.

Supported storages: arrays (not real storage, only for testing), files, 
memcached, redis, SQLite, MySQL (uses main db connection from app).

## Requirements

*   PHP >= 7.1
*   [php-strict/config](https://github.com/php-strict/config)

## Install

Install with [Composer](http://getcomposer.org):
    
```bash
composer require php-strict/storable-cache
```

## Usage

Basic usage:

```php
use PhpStrict\Config\Config
use PhpStrict\StorableCache\StorableCache

//instance of application configuration class, extending Config
//must provide cacheType property with correct storable cache type
//see PhpStrict\StorableCache\StorageTypes class
$config = new AppConfig();
$config->loadFromFile('config.ini');

//instance of StorableCache
$cache = new StorableCache($config);

//part of generating content method
if ($cache->has('contentKey') && !$cache->expired('contentKey')) {
    return $cache->get('contentKey');
}
//part of generating content method

//saving generated content: key, value, ttl, tts (time to save)
$cache->set('contentKey', $content, 60, 3600);
```

Usage if content generating main process was failed and it is correct to use expired data:

```php
use PhpStrict\StorableCache\StorableCache

//part of generating content method

//generating content failed

if ($cache->has('contentKey')) {
    return $cache->get('contentKey');
}

throw Exception('Generating content failed');
//part of generating content method
```

## Tests

To execute the test suite, you'll need [Codeception](https://codeception.com/).

```bash
vendor\bin\codecept run
```

[ico-license]: https://img.shields.io/badge/license-GPL-brightgreen.svg?style=flat-square
