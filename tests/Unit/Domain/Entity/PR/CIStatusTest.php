<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\CIStatus;

class CIStatusTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed_without_status_and_normalizes_itself()
    {
        $noStatus = CIStatus::noStatus();
        $this->assertEquals('NO_STATUS', $noStatus->stringValue());
    }

    /**
     * @test
     */
    public function it_can_be_constructed_with_green_status_and_normalizes_itself()
    {
        $noStatus = CIStatus::green();
        $this->assertEquals('GREEN', $noStatus->stringValue());
    }

    /**
     * @test
     */
    public function it_can_be_constructed_with_red_status_and_normalizes_itself()
    {
        $noStatus = CIStatus::red();
        $this->assertEquals('RED', $noStatus->stringValue());
    }

    /**
     * @test
     */
    public function it_can_be_constructed_from_normalized()
    {
        $noStatus = CIStatus::fromNormalized('GREEN');
        $this->assertEquals('GREEN', $noStatus->stringValue());
    }


    /**
     * @test
     */
    public function it_throws_if_it_does_not_support_a_status()
    {
        $this->expectException(\InvalidArgumentException::class);
        $noStatus = CIStatus::fromNormalized('UNSUPPORTED');
    }

    /**
     * @test
     */
    public function it_tells_if_it_is_green()
    {
        $this->assertTrue(CIStatus::green()->isGreen());
        $this->assertFalse(CIStatus::noStatus()->isGreen());
        $this->assertFalse(CIStatus::Red()->isGreen());
    }
}
