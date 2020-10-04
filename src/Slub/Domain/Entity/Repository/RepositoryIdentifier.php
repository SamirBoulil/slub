<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\Repository;

use Webmozart\Assert\Assert;

class RepositoryIdentifier
{
    /** @var string */
    private $repositoryIdentifier;

    private function __construct(string $repositoryIdentifier)
    {
        Assert::notEmpty($repositoryIdentifier);
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

    public function normalize(): string
    {
        return $this->repositoryIdentifier;
    }
}
