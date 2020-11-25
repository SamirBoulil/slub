<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\BuildResult;

class BuildResultTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed_with_status_pending_and_normalizes_itself(): void
    {
        $noStatus = BuildResult::pending();
        $this->assertEquals('PENDING', $noStatus->stringValue());
    }

    /**
     * @test
     */
    public function it_can_be_constructed_with_green_status_and_normalizes_itself(): void
    {
        $noStatus = BuildResult::green();
        $this->assertEquals('GREEN', $noStatus->stringValue());
    }

    /**
     * @test
     */
    public function it_can_be_constructed_with_red_status_and_normalizes_itself(): void
    {
        $noStatus = BuildResult::red();
        $this->assertEquals('RED', $noStatus->stringValue());
    }

    /**
     * @test
     */
    public function it_can_be_constructed_from_normalized(): void
    {
        $noStatus = BuildResult::fromNormalized('GREEN');
        $this->assertEquals('GREEN', $noStatus->stringValue());
    }

    /**
     * @test
     */
    public function it_throws_if_it_does_not_support_a_status(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        BuildResult::fromNormalized('UNSUPPORTED');
    }

    /**
     * @test
     */
    public function it_is_normalizable(): void
    {
        $this->assertEquals(BuildResult::green()->stringValue(), 'GREEN');
        $this->assertEquals(BuildResult::pending()->stringValue(), 'PENDING');
        $this->assertEquals(BuildResult::red()->stringValue(), 'RED');
    }

    /**
     * @test
     */
    public function it_tells_if_it_is_green(): void
    {
        $this->assertTrue(BuildResult::green()->isGreen());
        $this->assertFalse(BuildResult::green()->isRed());
        $this->assertFalse(BuildResult::green()->isPending());
    }

    /**
     * @test
     */
    public function it_tells_if_it_is_red(): void
    {
        $this->assertTrue(BuildResult::red()->isRed());
        $this->assertFalse(BuildResult::red()->isGreen());
        $this->assertFalse(BuildResult::red()->isPending());
    }

    /**
     * @test
     */
    public function it_tells_if_it_is_pending(): void
    {
        $this->assertTrue(BuildResult::pending()->isPending());
        $this->assertFalse(BuildResult::pending()->isRed());
        $this->assertFalse(BuildResult::pending()->isGreen());
    }
}
