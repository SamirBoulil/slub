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
    private $identifier;

    private function __construct(PRIdentifier $identifier)
    {
        $this->identifier = $identifier;
    }

    public static function withIdentifier(PRIdentifier $identifier): self
    {
        return new self($identifier);
    }

    public function identifier(): PRIdentifier
    {
        return $this->identifier;
    }
}
