<?php

declare(strict_types=1);

namespace Slub\Domain\Event;

use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PRIdentifier;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PRPutToReview extends Event
{
    private function __construct(private PRIdentifier $PRIdentifier, private MessageIdentifier $messageIdentifier)
    {
    }

    public static function forPR(PRIdentifier $PRIdentifier, MessageIdentifier $messageIdentifier): self
    {
        return new self($PRIdentifier, $messageIdentifier);
    }

    public function PRIdentifier(): PRIdentifier
    {
        return $this->PRIdentifier;
    }

    public function messageIdentifier(): MessageIdentifier
    {
        return $this->messageIdentifier;
    }
}
