<?php declare(strict_types=1);

namespace Nimbly\Throttler;

interface StorageAdapter
{
    /**
     * Get the current throttler count for the given key.
     *
     * @param string $key Unique key for source of request.
     * @return int
     */
    public function get(string $key): int;

    /**
     * Increment counter or create if it does not exist.
     *
     * @param string $key Unique key for source of request.
     * @param int $decay Number of seconds to decay counter.
     * @return int
     */
    public function increment(string $key, int $decay): int;
}