<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\BuildLink;
use Slub\Domain\Entity\PR\BuildResult;

class BuildLinkTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed_with_an_string_url_and_normalizes_itself()
    {
        $buildLink = 'https://my-ci.com/build/123';
        $noStatus = BuildLink::fromURL($buildLink);
        $this->assertEquals($buildLink, $noStatus->stringValue());
    }

    /**
     * @test
     */
    public function it_cannot_be_constructed_from_an_empty_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        BuildLink::fromURL('');
    }

    /**
     * @test
     */
    public function it_cannot_be_constructed_from_a_non_url_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        BuildLink::fromURL('build/123');
    }

    /**
     * @test
     */
    public function it_can_have_no_url()
    {
        $buildLink = BuildLink::none();
        self::assertEmpty($buildLink->stringValue());
    }

    /**
     * @test
     */
    public function it_can_be_created_from_normalized()
    {
        $normalizedBuildLink = 'https://my-ci.com/1213';

        $buildLink = BuildLink::fromNormalized($normalizedBuildLink);
        $noBuildLink = BuildLink::none();

        self::assertEquals($normalizedBuildLink, $buildLink->stringValue());
        self::assertEmpty($noBuildLink->stringValue());
    }

    /**
     * @test
     */
    public function it_tells_if_it_is_equal_to_another_build_link()
    {
        $buildLink = BuildLink::fromNormalized('https://my-ci.com/1213');
        $otherBuildLink = BuildLink::fromNormalized('https://my-ci.com/567');
        $noBuildLink = BuildLink::none();

        self::assertTrue($buildLink->equals($buildLink));
        self::assertFalse($buildLink->equals($otherBuildLink));
        self::assertFalse($buildLink->equals($noBuildLink));
    }
}
