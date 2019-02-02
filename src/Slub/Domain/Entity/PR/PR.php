<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

use Webmozart\Assert\Assert;

class PR
{
    private const IDENTIFIER_KEY = 'identifier';

    /** @var PRIdentifier */
    private $identifier;

    const GTM_KEY = 'GTM';

    /** @var int */
    private $GTMCount;

    private function __construct(PRIdentifier $identifier, int $GTMCount)
    {
        $this->identifier = $identifier;
        $this->GTMCount = $GTMCount;
    }

    public static function create(PRIdentifier $identifier): self
    {
        return new self($identifier, 0);
    }

    public static function fromNormalized(array $normalizedPR): self
    {
        Assert::keyExists($normalizedPR, self::IDENTIFIER_KEY);
        $identifier = PRIdentifier::fromString($normalizedPR[self::IDENTIFIER_KEY]);
        Assert::keyExists($normalizedPR, self::GTM_KEY);
        $GTM = $normalizedPR[self::GTM_KEY];

        return new self($identifier, $GTM);
    }

    public function identifier(): PRIdentifier
    {
        return $this->identifier;
    }

    public function normalize(): array
    {
        return [
            self::IDENTIFIER_KEY => $this->identifier()->stringValue(),
            self::GTM_KEY        => $this->GTMCount,
        ];
    }

    public function GTM(): void
    {
        $this->GTMCount++;
    }
}
