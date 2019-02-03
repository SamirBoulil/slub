<?php

declare(strict_types=1);

namespace Slub\Domain\Event;

use Slub\Domain\Entity\PR\PRIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class PRGTMed
{
    /** @var PRIdentifier */
    private $PRIdentifier;

    private function __construct(PRIdentifier $PRIdentifier)
    {
        $this->PRIdentifier = $PRIdentifier;
    }

    public static function withIdentifier(PRIdentifier $PRIdentifier): self
    {
        return new self($PRIdentifier);
    }

    public function PRIdentifier(): PRIdentifier
    {
        return $this->PRIdentifier;
    }
}
