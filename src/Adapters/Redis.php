<?php declare(strict_types=1);

namespace Throttler\Adapters;

use Predis\Client;
use Throttler\StorageAdapter;

/**
 * Redis cache adapter
 *
 * The redis cache adapter requries a connection to a redis instance and the *predis/predis* package.
 *
 * @package Throttler\Adapters
 *
 */
class Redis implements StorageAdapter
{
    /**
     * Predis\Client instance.
     *
     * @var Client
     */
    protected $client;

    /**
     * Create a Redis storage interface with a Predis\Client instance.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritDoc
     */
    public function get($key): int
    {
        return (int) $this->client->get($key);
    }

    /**
     * @inheritDoc
     */
    public function increment($key, $decay): int
    {
        if( $this->client->setnx($key, 1) ){
            $this->client->expire($key, $decay);
            return 1;
        }

        return (int) $this->client->incr($key);
    }
}