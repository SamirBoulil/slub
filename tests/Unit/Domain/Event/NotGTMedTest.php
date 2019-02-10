<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\NotGTMed;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class NotGTMedTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_created_with_a_pr_identifier_and_returns_it()
    {
        $expectedIdentifier = PRIdentifier::create('akeneo/pim-community-dev/1010');
        $event = NotGTMed::forPR($expectedIdentifier);
        $this->assertTrue($event->PRIdentifier()->equals($expectedIdentifier),
            'Expected identifier to be the same than the one the event was created with, found different.');
    }
}
