<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\InMemory;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\InMemory\InMemoryGetPRInfo;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class InMemoryGetPRInfoTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_a_PR_info_for_the_given_identifier_and_some_default_data()
    {
        $expectedPRIdentifier = 'akeneo/pim-community-dev';

        $getPRInfo = new InMemoryGetPRInfo();
        $actualPRInfo = $getPRInfo->fetch(PRIdentifier::fromString($expectedPRIdentifier));

        self::assertEquals($expectedPRIdentifier, $actualPRInfo->PRIdentifier);
        self::assertEquals('sam', $actualPRInfo->authorIdentifier);
        self::assertEquals('Add new feature', $actualPRInfo->title);
        self::assertEquals('GREEN', $actualPRInfo->CIStatus->status);
        self::assertEquals('', $actualPRInfo->CIStatus->buildLink);
        self::assertEquals(0, $actualPRInfo->comments);
        self::assertEquals(0, $actualPRInfo->GTMCount);
        self::assertEquals(0, $actualPRInfo->notGTMCount);
        self::assertEquals(false, $actualPRInfo->isMerged);
    }
}
