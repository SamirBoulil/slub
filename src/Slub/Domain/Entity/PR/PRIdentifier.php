<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

use Webmozart\Assert\Assert;

class PRIdentifier
{
    private string $PRIdentifier;

    private function __construct(string $PRIdentifier)
    {
        Assert::notEmpty($PRIdentifier);
        $this->PRIdentifier = $PRIdentifier;
    }

    public static function create(string $PRIdentifier): self
    {
        return new self($PRIdentifier);
    }

    /**
     * @deprecated use from PR Information instead.
     */
    public static function fromString(string $PRIdentifier): self
    {
        return new self($PRIdentifier);
    }

    public static function fromPRInfo(string $repositoryFullName, string $prNumber): self
    {
        return new self(sprintf('%s/%s', $repositoryFullName, $prNumber));
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
