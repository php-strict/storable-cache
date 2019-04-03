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
 * Incapsulating value and service data for cache item.
 */
class Packet
{
    /**
     * @var string
     */
    protected $value;
    
    /**
     * @var int
     */
    protected $timestamp;
    
    /**
     * @var int
     */
    protected $lifetime;
    
    /**
     * @var int
     */
    protected $savetime;
    
    /**
     * @param string $value
     * @param int $timestamp = 0
     */
    public function __construct(string $value, int $lifetime, int $savetime, int $timestamp = null)
    {
        $this->value = $value;
        $this->lifetime = $lifetime;
        $this->savetime = $savetime;
        if (null === $timestamp || 0 > $timestamp) {
            $this->timestamp = time();
        } else {
            $this->timestamp = $timestamp;
        }
    }
    
    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
    
    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }
    
    /**
     * @return int
     */
    public function getLifetime(): int
    {
        return $this->lifetime;
    }
    
    /**
     * @return int
     */
    public function getSavetime(): int
    {
        return $this->savetime;
    }
    
    /**
     * @return bool
     */
    public function expired(): bool
    {
        return $this->lifetime < (time() - $this->timestamp);
    }
    
    /**
     * @return bool
     */
    public function outdated(): bool
    {
        return $this->savetime < (time() - $this->timestamp);
    }
}
