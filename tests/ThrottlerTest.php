<?php

namespace Throttler\Tests;

use PHPUnit\Framework\TestCase;
use Throttler\Adapters\Memory;
use Throttler\Throttler;

/**
 * @covers Throttler\Throttler
 * @covers Throttler\Adapters\Memory
 */
class ThrottlerTest extends TestCase
{
    /** @var string */
    protected $testKey = 'TestKey';

    /** @var Throttler */
    protected $throttler;

    public function setup()
    {
        $this->throttler = new Throttler(new Memory);
    }

    /**
     *
     * Tests that nonexistent keys still increment.
     *
     * @return void
     */
    public function test_increments_nonexistent_key()
    {
        $this->throttler->hit($this->testKey, 5, 60);
        $this->assertEquals(1, $this->throttler->check($this->testKey));
    }

    /**
     * Tests that existing keys increment.
     *
     * @return void
     */
    public function test_increments_existing_key()
    {
        $this->throttler->hit($this->testKey, 5, 60);
        $this->throttler->hit($this->testKey, 5, 60);

        $this->assertEquals(2, $this->throttler->check($this->testKey));
    }

    /**
     * Tests that hitting the throttler after the defined limit returns false.
     *
     * @return void
     */
    public function test_hitting_limit_returns_false()
    {
        for( $i=0; $i < 5; $i++ ){
            $this->throttler->hit($this->testKey, 5, 60);
        }

        $this->assertFalse($this->throttler->hit($this->testKey, 5, 60));
    }

    /**
     * Tests that hitting throttler while still under the limit returns true.
     *
     * @return void
     */
    public function test_under_limit_returns_true()
    {
        $this->assertTrue($this->throttler->hit($this->testKey, 5, 60));
    }
}