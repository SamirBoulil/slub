<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

use Webmozart\Assert\Assert;

class PRIdentifier
{
    /** @var string */
    private $PRIdentifier;

    private function __construct(string $PRIdentifier)
    {
        Assert::notEmpty($PRIdentifier);
        $this->PRIdentifier = $PRIdentifier;
    }

    public static function create(string $PRIdentifier): self
    {
        return new self($PRIdentifier);
    }

    public static function fromString(string $PRIdentifier): self
    {
        return new self($PRIdentifier);
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
