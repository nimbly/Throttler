<?php

namespace Tests\Adapters;

use Throttler\StorageAdapter;

class Memory implements StorageAdapter
{
    /** @var array */
    protected $keys = [];

    /**
     * @inheritDoc
     */
    public function get($key)
    {
        if( ($record = $this->getRecord($key)) === null ){
            return 0;
        }

        return $record['count'];
    }

    /**
     * @inheritDoc
     */
    public function increment($key, $decay)
    {
        if( ($record = $this->getRecord($key)) === null ||
            $record['expires_at'] < time() ){

            $this->keys[$key] = [
                'count' => 0,
                'expires_at' => (int) (time() + $decay),
            ];
        }

        return ++$this->keys[$key]['count'];
    }

    /**
     * Get a record from the keys or return null if key not found.
     * 
     * @param string $key
     * @return mixed
     */
    private function getRecord($key)
    {
        if( array_key_exists($key, $this->keys) ){
            return $this->keys[$key];
        }

        return null;
    }
}

