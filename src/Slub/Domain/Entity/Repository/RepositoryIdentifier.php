<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\Repository;

class RepositoryIdentifier
{
    /** @var string */
    private $repositoryIdentifier;

    private function __construct(string $repositoryIdentifier)
    {
        $this->repositoryIdentifier = $repositoryIdentifier;
    }

    public static function fromString(string $repositoryIdentifier): self
    {
        return new self($repositoryIdentifier);
    }

    public function equals(RepositoryIdentifier $repositoryIdentifier): bool
    {
        return $this->repositoryIdentifier === $repositoryIdentifier->repositoryIdentifier;
    }
}
