<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

use Webmozart\Assert\Assert;

class PR
{
    private const IDENTIFIER_KEY = 'identifier';
    private const GTM_KEY = 'GTM';

    /** @var PRIdentifier */
    private $PRIdentifier;

    /** @var int */
    private $GTMCount;

    private function __construct(PRIdentifier $PRIdentifier, int $GTMCount)
    {
        $this->PRIdentifier = $PRIdentifier;
        $this->GTMCount = $GTMCount;
    }

    public static function create(PRIdentifier $PRIdentifier): self
    {
        return new self($PRIdentifier, 0);
    }

    public static function fromNormalized(array $normalizedPR): self
    {
        Assert::keyExists($normalizedPR, self::IDENTIFIER_KEY);
        $identifier = PRIdentifier::fromString($normalizedPR[self::IDENTIFIER_KEY]);
        Assert::keyExists($normalizedPR, self::GTM_KEY);
        $GTM = $normalizedPR[self::GTM_KEY];

        return new self($identifier, $GTM);
    }

    public function PRIdentifier(): PRIdentifier
    {
        return $this->PRIdentifier;
    }

    public function normalize(): array
    {
        return [
            self::IDENTIFIER_KEY => $this->PRIdentifier()->stringValue(),
            self::GTM_KEY        => $this->GTMCount,
        ];
    }

    public function GTM(): void
    {
        $this->GTMCount++;
    }
}
