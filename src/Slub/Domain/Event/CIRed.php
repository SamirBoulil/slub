<?php

declare(strict_types=1);

namespace Slub\Domain\Event;

use Slub\Domain\Entity\PR\BuildLink;
use Slub\Domain\Entity\PR\PRIdentifier;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CIRed extends Event
{
    /** @var PRIdentifier */
    private $PRIdentifier;

    /** @var BuildLink */
    private $buildLink;

    private function __construct(PRIdentifier $PRIdentifier, BuildLink $buildLink)
    {
        $this->PRIdentifier = $PRIdentifier;
        $this->buildLink = $buildLink;
    }

    public static function forPR(PRIdentifier $PRIdentifier, BuildLink $buildLink): self
    {
        return new self($PRIdentifier, $buildLink);
    }

    public function PRIdentifier(): PRIdentifier
    {
        return $this->PRIdentifier;
    }

    public function buildLink(): BuildLink
    {
        return $this->buildLink;
    }
}
