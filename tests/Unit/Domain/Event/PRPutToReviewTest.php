<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\PRPutToReview;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PRPutToReviewTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_created_with_a_pr_identifier_and_message_id_returns_them()
    {
        $expectedIdentifier = PRIdentifier::create('akeneo/pim-community-dev/1010');
        $expectedMessageIdentifier = MessageIdentifier::fromString('channel@1541');

        $event = PRPutToReview::forPR($expectedIdentifier, $expectedMessageIdentifier);

        $this->assertTrue($event->PRIdentifier()->equals($expectedIdentifier),
            'Expected identifier to be the same than the one the event was created with, found different.');
        $this->assertTrue($event->messageIdentifier()->equals($expectedMessageIdentifier),
            'Expected message identifier to be the same than the one the event was created with, found different.');
    }
}
