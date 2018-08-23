<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Adapters\Memory;
use Throttler\Throttler;

class ThrottlerTest extends TestCase
{
    /** @var string */
    protected $testKey = 'TestKey';

    /** @var Throttler */
    protected $throttler;

    public function setup()
    {
        $memoryAdapter = new Memory;
        $this->throttler = new Throttler($memoryAdapter);
    }

    public function test_increments_nonexistent_key()
    {
        $this->throttler->hit($this->testKey, 5, 60);
        $this->assertEquals(1, $this->throttler->check($this->testKey));
    }

    public function test_increments_existing_key()
    {
        $this->throttler->hit($this->testKey, 5, 60);
        $this->throttler->hit($this->testKey, 5, 60);

        $this->assertEquals(2, $this->throttler->check($this->testKey));
    }

    public function test_hitting_limit_returns_false()
    {
        for( $i=0; $i < 5; $i++ ){
            $this->throttler->hit($this->testKey, 5, 60);
        }
        
        $this->assertFalse($this->throttler->hit($this->testKey, 5, 60));
    }

    public function test_under_limit_returns_true()
    {
        $this->assertTrue($this->throttler->hit($this->testKey, 5, 60));
    }
}