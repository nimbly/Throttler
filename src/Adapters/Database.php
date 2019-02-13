<?php

namespace Throttler\Adapters;

use PDO;
use Throttler\StorageAdapter;

/**
 * Database (PDO) adapter
 * The database adapter uses any PDO compatible database.
 * 
 * @package Throttle\Adapters
 */
class Database implements StorageAdapter
{
    /** @var PDO */
    protected $pdo;

    /** @var string */
    protected $table = "throttler";

    // Columns

    /** @var string */
    protected $keyColumn = "key";

    /** @var string */
    protected $hitsColumn = "hits";

    /** @var string */
    protected $expiresAtColumn = "expires_at";

    // Garbage collection

    /** @var int */
    protected $garbageCollectionChance = 5;

    /**
     * Database adapter constructor.
     *
     * @param PDO $pdo
     * @param array $options
     */
    public function __construct(PDO $pdo, array $options = [])
    {
        $this->pdo = $pdo;

        $this->table = $options['table'] ?? $this->table;
        $this->keyColumn = $options['key'] ?? $this->keyColumn;
        $this->hitsColumn = $options['hits'] ?? $this->hitsColumn;
        $this->expiresAtColumn = $options['expires_at'] ?? $this->expiresAtColumn;
        $this->garbageCollectionChance = $options['gc_chance'] ?? $this->garbageCollectionChance;
    }

    /**
     * @inheritDoc
     */
    public function get($key)
    {
        $statement = $this->pdo->prepare("select {$this->hitsColumn} from {$this->table} where {$this->keyColumn} = ?");
        $statement->execute([$key]);
        return (int) $statement->fetchColumn();
    }

    /**
     * @inheritDoc
     */
    public function increment($key, $decay)
    {
        // Record not found, lets create a new one.
        if( ($record = $this->fetchRecord($key)) == null ){

            $statement = $this->pdo->prepare("insert into {$this->table} ({$this->keyColumn}, {$this->hitsColumn}, {$this->expiresAtColumn}) values (?, ?, ?)");
            $response = $statement->execute([$key, 1, time() + $decay]);
            return 1;
        }

        // Record exists, grab the current hits count.
        else {
            $hits = $record->{$this->hitsColumn};
        }

        // Update the table.
        $statement = $this->pdo->prepare("update {$this->table} set {$this->hitsColumn} = ? where {$this->keyColumn} = ?");
        $statement->execute([++$hits, $key]);

        // Run garbage collection?
        if( $this->garbageCollectionChance > 0 &&
            $this->garbageCollectionChance >= mt_rand(1, 100) ){
            $this->gc();
        }

        return $hits;
    }

    /**
     * Fetch a record from the database.
     *
     * @param string $key
     * @return \StdClass
     */
    protected function fetchRecord($key)
    {
        $statement = $this->pdo->prepare("select * from {$this->table} where {$this->keyColumn} = ?");
        $statement->execute([$key]);
        return $statement->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Remove expired records from table.
     *
     * @return void
     */
    protected function gc()
    {
        $statement = $this->pdo->prepare("delete from {$this->table} where {$this->expiresAtColumn} < ?");
        $statement->execute([time()]);
    }
}