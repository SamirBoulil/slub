<?php

declare(strict_types=1);

namespace Slub\Domain\Event;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Reviewer\ReviewerName;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PRCommented extends Event
{
    private function __construct(private PRIdentifier $PRIdentifier, private ReviewerName $reviewerName)
    {
    }

    public static function forPR(PRIdentifier $PRIdentifier, ReviewerName $reviewerName): self
    {
        return new self($PRIdentifier, $reviewerName);
    }

    public function PRIdentifier(): PRIdentifier
    {
        return $this->PRIdentifier;
    }

    public function reviewerName(): ReviewerName
    {
        return $this->reviewerName;
    }
}
