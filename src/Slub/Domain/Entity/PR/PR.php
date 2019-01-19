<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

use Webmozart\Assert\Assert;

class PR
{
    private const IDENTIFIER_KEY = 'identifier';

    /** @var PRIdentifier */
    private $identifier;

    private function __construct(PRIdentifier $identifier)
    {
        $this->identifier = $identifier;
    }

    public static function create(PRIdentifier $identifier): self
    {
        return new self($identifier);
    }

    public static function fromNormalized(array $normalizedPR): self
    {
        Assert::keyExists($normalizedPR, self::IDENTIFIER_KEY);
        $identifier = PRIdentifier::fromString($normalizedPR[self::IDENTIFIER_KEY]);

        return new self($identifier);
    }

    public function identifier(): PRIdentifier
    {
        return $this->identifier;
    }

    public function normalize(): array
    {
        return [
            self::IDENTIFIER_KEY => $this->identifier()->stringValue()
        ];
    }
}
