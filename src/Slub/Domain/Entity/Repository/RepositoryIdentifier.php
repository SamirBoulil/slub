<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\Repository;

class RepositoryIdentifier
{
    /** @var string */
    private $identifier;

    private function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public static function fromString(string $identifier): self
    {
        return new self($identifier);
    }

    public function equals(RepositoryIdentifier $repositoryIdentifier): bool
    {
        return $this->identifier === $repositoryIdentifier->identifier;
    }
}
