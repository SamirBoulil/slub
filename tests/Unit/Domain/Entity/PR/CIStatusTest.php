<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use Slub\Domain\Entity\PR\BuildLink;
use Slub\Domain\Entity\PR\BuildResult;
use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\CIStatus;

class CIStatusTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_created_from_a_build_result_and_a_build_link_and_normalizes_it_self()
    {
        $buildLink = 'https://my-ci.com/build/123';
        $buildResult = BuildResult::green();

        $ciStatus = CIStatus::endedWith($buildResult, BuildLink::fromURL($buildLink));
        $normalizedCIStatus = $ciStatus->normalize();

        self::assertEquals(
            [
                'BUILD_RESULT' => 'GREEN',
                'BUILD_LINK'   => $buildLink,
            ],
            $normalizedCIStatus
        );
    }

    /**
     * @test
     */
    public function it_is_created_from_a_build_result_and_no_build_link_and_normalizes_it_self()
    {
        $buildResult = BuildResult::green();

        $ciStatus = CIStatus::endedWith($buildResult, BuildLink::none());
        $normalizedCIStatus = $ciStatus->normalize();

        self::assertEquals(
            [
                'BUILD_RESULT' => 'GREEN',
                'BUILD_LINK'   => '',
            ],
            $normalizedCIStatus
        );
    }

    /**
     * @test
     */
    public function it_is_created_from_a_normalized_version()
    {
        $expectedNormalizedCIStatus = [
            'BUILD_RESULT' => 'GREEN',
            'BUILD_LINK'   => '',
        ];

        $CIStatus = CIStatus::fromNormalized($expectedNormalizedCIStatus);
        $actualNormalizedCIStatus = $CIStatus->normalize();

        self::assertEquals($expectedNormalizedCIStatus, $actualNormalizedCIStatus);
    }

    /**
     * @test
     */
    public function it_tells_is_the_result_is_green()
    {
        $CIStatus = CIStatus::endedWith(BuildResult::green(), BuildLink::none());
        self::assertTrue($CIStatus->isGreen());
        self::assertFalse($CIStatus->isRed());
        self::assertFalse($CIStatus->isPending());
    }

    /**
     * @test
     */
    public function it_tells_is_the_result_is_pending()
    {
        $CIStatus = CIStatus::endedWith(BuildResult::pending(), BuildLink::none());
        self::assertTrue($CIStatus->isPending());
        self::assertFalse($CIStatus->isGreen());
        self::assertFalse($CIStatus->isRed());
    }
}
