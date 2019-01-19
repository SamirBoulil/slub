<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\Channel;

class ChannelIdentifier
{
    /** @var string */
    private $identifier;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public static function fromString(string $identifier): self
    {
        return new self($identifier);
    }

    public function stringValue(): string
    {
        return $this->identifier;
    }

    public function equals(ChannelIdentifier $identifier): bool
    {
        return $identifier->identifier === $this->identifier;
    }
}
