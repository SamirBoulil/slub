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

        $ciStatus = CIStatus::endedWith($buildResult);
        $normalizedCIStatus = $ciStatus->normalize();

        self::assertEquals(
            [
                'BUILD_RESULT' => 'GREEN',
                'BUILD_LINK'   => null,
            ],
            $normalizedCIStatus
        );
    }

    /**
     * @test
     */
    public function it_is_created_from_a_normlized_version()
    {
        $expectedNormalizedCIStatus = [
            'BUILD_RESULT' => 'GREEN',
            'BUILD_LINK'   => null,
        ];

        $CIStatus = CIStatus::fromNormalized($expectedNormalizedCIStatus);
        $actualNormalizedCIStatus = $CIStatus->normalize();

        self::assertEquals($expectedNormalizedCIStatus, $actualNormalizedCIStatus);
    }

}
