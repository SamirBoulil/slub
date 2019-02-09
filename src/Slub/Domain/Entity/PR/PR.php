<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIRed;
use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Event\PRNotGTMed;
use Symfony\Component\EventDispatcher\Event;
use Webmozart\Assert\Assert;

class PR
{
    private const IDENTIFIER_KEY = 'identifier';
    private const GTM_KEY = 'GTM';
    private const NOTGTM_KEY = 'NOT_GTM';
    private const CI_STATUS_KEY = 'CI_STATUS';

    /** @var Event[] */
    private $events = [];

    /** @var PRIdentifier */
    private $PRIdentifier;

    /** @var int */
    private $GTMCount;

    /** @var int */
    private $notGTMCount;

    /** @var CIStatus */
    private $CIStatus;

    private function __construct(PRIdentifier $PRIdentifier, int $GTMCount, int $notGTMCount, CIStatus $CIStatus)
    {
        $this->PRIdentifier = $PRIdentifier;
        $this->GTMCount = $GTMCount;
        $this->notGTMCount = $notGTMCount;
        $this->CIStatus = $CIStatus;
    }

    public static function create(PRIdentifier $PRIdentifier): self
    {
        return new self($PRIdentifier, 0, 0, CIStatus::noStatus());
    }

    public static function fromNormalized(array $normalizedPR): self
    {
        Assert::keyExists($normalizedPR, self::IDENTIFIER_KEY);
        $identifier = PRIdentifier::fromString($normalizedPR[self::IDENTIFIER_KEY]);
        Assert::keyExists($normalizedPR, self::GTM_KEY);
        $GTM = $normalizedPR[self::GTM_KEY];
        $NOTGTM = $normalizedPR[self::NOTGTM_KEY];
        $CIStatus = $normalizedPR[self::CI_STATUS_KEY] ?? '';

        return new self($identifier, $GTM, $NOTGTM, CIStatus::fromNormalized($CIStatus));
    }

    public function normalize(): array
    {
        return [
            self::IDENTIFIER_KEY => $this->PRIdentifier()->stringValue(),
            self::GTM_KEY        => $this->GTMCount,
            self::NOTGTM_KEY     => $this->notGTMCount,
            self::CI_STATUS_KEY  => $this->CIStatus->stringValue(),
        ];
    }

    public function PRIdentifier(): PRIdentifier
    {
        return $this->PRIdentifier;
    }

    public function GTM(): void
    {
        $this->GTMCount++;
        $this->events[] = PRGTMed::withIdentifier($this->PRIdentifier);
    }

    public function notGTM(): void
    {
        $this->notGTMCount++;
        $this->events[] = PRNotGTMed::withIdentifier($this->PRIdentifier);
    }

    /**
     * @return Event[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function CIIsGreen(): void
    {
        $this->CIStatus = CIStatus::green();
        $this->events[] = CIGreen::ForPR($this->PRIdentifier);
    }

    public function isGreen(): bool
    {
        return $this->CIStatus->isGreen();
    }

    public function CIIsRed(): void
    {
        $this->CIStatus = CIStatus::red();
        $this->events[] = CIRed::ForPR($this->PRIdentifier);
    }

    public function isRed(): bool
    {
        return $this->CIStatus->isRed();
    }
}
