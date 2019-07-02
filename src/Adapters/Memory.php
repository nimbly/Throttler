<?php declare(strict_types=1);

namespace Throttler\Adapters;

use Throttler\StorageAdapter;

/**
 * Memory adapter
 *
 * The memory adapter maintains state only within the current request or for the duration of a script.
 *
 * @package Throttler\Adapters
 *
 */
class Memory implements StorageAdapter
{
    /**
     * Array of throttler keys.
     *
     * @var array<string, array>
     */
    protected $keys = [];

    /**
     * @inheritDoc
     */
    public function get($key): int
    {
        if( ($record = $this->getRecord($key)) === null ){
            return 0;
        }

        return $record['count'];
    }

    /**
     * @inheritDoc
     */
    public function increment($key, $decay): int
    {
        if( ($record = $this->getRecord($key)) === null ||
            $record['expires_at'] < time() ){

            $this->keys[$key] = [
                'count' => 0,
                'expires_at' => (int) (\time() + $decay),
            ];
        }

        return ++$this->keys[$key]['count'];
    }

    /**
     * Get a record from the keys or return null if key not found.
     *
     * @param string $key
     * @return array|null
     */
    private function getRecord($key): ?array
    {
        if( \array_key_exists($key, $this->keys) ){
            return $this->keys[$key];
        }

        return null;
    }
}