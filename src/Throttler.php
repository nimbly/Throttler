<?php declare(strict_types=1);

namespace Nimbly\Throttler;


class Throttler
{
    /**
     * The StorageAdapter instance.
     *
     * @var StorageAdapter
     */
    protected $storageAdapter;

    /**
     * Key prefix prepended to keys before passing to StorageAdapter.
     *
     * @var string
     */
    protected $keyPrefix = "Throttler\\";

    /**
     * Throttler constructor.
     *
     * Options may contain any of the following key/value pairs:
     *      "prefix": String to be prepended to keys before being passed to StorageAdapter. Defaults to "Throttler\".
     *
     *
     * @param StorageAdapter $storageAdapter
     * @param array $options
     */
    public function __construct(StorageAdapter $storageAdapter, array $options = [])
    {
        $this->storageAdapter = $storageAdapter;

        $this->keyPrefix = $options['prefix'] ?? $this->keyPrefix;
    }

    /**
     * Get the current counter.
     *
     * @param string $id
     * @return int
     */
    public function check(string $id): int
    {
        return $this->storageAdapter->get($this->makeKey($id));
    }

    /**
     * Hit the throttle counter.
     *
     * @param string $id The ID you'd like to group requests by. Could be IP address, a user ID, or any value that uniquely identifies the source of a request.
     * @param int $limit Number of requests allowed in time span
     * @param int $decay Time span in seconds.
     * @return boolean
     */
    public function hit(string $id, int $limit, int $decay): bool
    {
        if( $this->storageAdapter->increment($this->makeKey($id), $decay) > $limit ){
            return false;
        }

        return true;
    }

    /**
     * Make the cache key
     *
     * @param string $id
     * @return string
     */
    protected function makeKey($id): string
    {
        return "{$this->keyPrefix}{$id}";
    }
}