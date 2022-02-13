<?php

declare(strict_types=1);

namespace Slub\Domain\Event;

use Slub\Domain\Entity\PR\PRIdentifier;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author    Pierrick Martos <pierrick.martos@gmail.com>
 */
class PRTooLarge extends Event
{
    private function __construct(private PRIdentifier $PRIdentifier)
    {
    }

    public static function forPR(PRIdentifier $PRIdentifier): self
    {
        return new self($PRIdentifier);
    }

    public function PRIdentifier(): PRIdentifier
    {
        return $this->PRIdentifier;
    }
}
