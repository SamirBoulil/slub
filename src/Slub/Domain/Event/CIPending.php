<?php

declare(strict_types=1);

namespace Slub\Domain\Event;

use Slub\Domain\Entity\PR\PRIdentifier;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CIPending extends Event
{
    /** @var PRIdentifier */
    private $PRIdentifier;

    private function __construct(PRIdentifier $PRIdentifier)
    {
        $this->PRIdentifier = $PRIdentifier;
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
