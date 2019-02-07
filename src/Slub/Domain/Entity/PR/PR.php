<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

use Webmozart\Assert\Assert;

class PR
{
    private const IDENTIFIER_KEY = 'identifier';
    private const GTM_KEY = 'GTM';
    private const NOTGTM_KEY = 'NOT_GTM';

    /** @var PRIdentifier */
    private $PRIdentifier;

    /** @var int */
    private $GTMCount;

    /** @var int */
    private $notGTMCount;

    private function __construct(PRIdentifier $PRIdentifier, int $GTMCount, int $notGTMCount)
    {
        $this->PRIdentifier = $PRIdentifier;
        $this->GTMCount = $GTMCount;
        $this->notGTMCount = $notGTMCount;
    }

    public static function create(PRIdentifier $PRIdentifier): self
    {
        return new self($PRIdentifier, 0, 0);
    }

    public static function fromNormalized(array $normalizedPR): self
    {
        Assert::keyExists($normalizedPR, self::IDENTIFIER_KEY);
        $identifier = PRIdentifier::fromString($normalizedPR[self::IDENTIFIER_KEY]);
        Assert::keyExists($normalizedPR, self::GTM_KEY);
        $GTM = $normalizedPR[self::GTM_KEY];
        $NOTGTM = $normalizedPR[self::NOTGTM_KEY];

        return new self($identifier, $GTM, $NOTGTM);
    }

    public function normalize(): array
    {
        return [
            self::IDENTIFIER_KEY => $this->PRIdentifier()->stringValue(),
            self::GTM_KEY        => $this->GTMCount,
            self::NOTGTM_KEY     => $this->notGTMCount,
        ];
    }

    public function PRIdentifier(): PRIdentifier
    {
        return $this->PRIdentifier;
    }

    public function GTM(): void
    {
        $this->GTMCount++;
    }

    public function notGTM(): void
    {
        $this->notGTMCount++;
    }
}
