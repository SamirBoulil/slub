<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\PRClosed;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class PRClosedTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_created_with_a_pr_identifier_and_returns_it()
    {
        $expectedIdentifier = PRIdentifier::create('akeneo/pim-community-dev/1010');
        $event = PRClosed::forPR($expectedIdentifier);
        $this->assertTrue(
            $event->PRIdentifier()->equals($expectedIdentifier),
            'Expected identifier to be the same than the one the event was created with, found different.'
        );
    }
}
