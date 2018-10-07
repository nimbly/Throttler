<?php

namespace Throttler\Adapters;

use Throttler\StorageAdapter;

/**
 * APCu cache adapter
 * 
 * @package Throttler\Adapters
 * 
 */
class Apcu implements StorageAdapter
{
    /**
     * @inheritDoc
     */
    public function get($key)
    {
        $hits = apcu_fetch($key, $success);

        if( !$success ){
            return 0;
        }

        return (int) $hits;
    }

    /**
     * @inheritDoc
     */
    public function increment($key, $decay)
    {
        if( apcu_exists($key) == false ){
            apcu_add($key, 1, (int) $decay);
            return 1;
        }

        return (int) apcu_inc($key);
    }
}