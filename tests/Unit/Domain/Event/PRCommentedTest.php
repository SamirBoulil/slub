<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Reviewer\ReviewerName;
use Slub\Domain\Event\PRCommented;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PRCommentedTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_created_with_a_pr_identifier_and_returns_it()
    {
        $expectedPRIdentifier = PRIdentifier::create('akeneo/pim-community-dev/1010');
        $expectedReviewerName = ReviewerName::fromString('Samir');

        $event = PRCommented::forPR($expectedPRIdentifier, $expectedReviewerName);

        $this->assertTrue(
            $event->PRIdentifier()->equals($expectedPRIdentifier),
            'Expected PR identifier to be the same than the one the event was created with, found different.'
        );
        $this->assertEquals(
            $event->reviewerName()->stringValue(),
            $expectedReviewerName->stringValue(),
            'Expected reviewer name to be the same than the one the event was created with, found different.'
        );
    }
}
