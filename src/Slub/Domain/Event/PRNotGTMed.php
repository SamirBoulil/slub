<?php

declare(strict_types=1);

namespace Slub\Domain\Event;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Reviewer\ReviewerName;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PRNotGTMed extends Event
{
    /** @var PRIdentifier */
    private $PRIdentifier;

    /** @var ReviewerName */
    private $reviewerName;

    private function __construct(PRIdentifier $PRIdentifier, ReviewerName $reviewerName)
    {
        $this->PRIdentifier = $PRIdentifier;
        $this->reviewerName = $reviewerName;
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
