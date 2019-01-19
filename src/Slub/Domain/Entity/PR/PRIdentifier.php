<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

class PRIdentifier
{
    /** @var string */
    private $identifier;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public static function create(string $repository, string $externalIdentifier): self
    {
        $identifier = sprintf('%s/%s', $repository, $externalIdentifier);

        return new self($identifier);
    }

    public static function fromString(string $identifier): self
    {
        return new self($identifier);
    }

    public function stringValue(): string
    {
        return $this->identifier;
    }

    public function equals(PRIdentifier $identifier): bool
    {
        return $identifier->identifier === $this->identifier;
    }
}
