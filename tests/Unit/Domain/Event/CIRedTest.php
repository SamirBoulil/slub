<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\BuildLink;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\CIRed;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CIRedTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_created_with_a_pr_identifier_and_build_link_and_returns_it()
    {
        $expectedIdentifier = PRIdentifier::create('akeneo/pim-community-dev/1010');
        $buildLink = 'http://my-ci.com/build/123';
        $event = CIRed::forPR($expectedIdentifier, BuildLink::fromURL($buildLink));
        $this->assertTrue(
            $event->PRIdentifier()->equals($expectedIdentifier),
            'Expected identifier to be the same than the one the event was created with, found different.'
        );
        $this->assertEquals($event->buildLink()->stringValue(), $buildLink);
    }
}
