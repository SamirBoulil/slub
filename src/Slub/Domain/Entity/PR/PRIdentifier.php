<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

class PRIdentifier
{
    /** @var string */
    private $PRIdentifier;

    private function __construct(string $PRIdentifier)
    {
        $this->PRIdentifier = $PRIdentifier;
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
        return $this->PRIdentifier;
    }

    public function equals(PRIdentifier $identifier): bool
    {
        return $identifier->PRIdentifier === $this->PRIdentifier;
    }
}
