<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class MergedAt
{
    private const EMPTY_DATE = null;

    /** @var ?\DateTime */
    private $mergedAt;

    public function __construct(?\DateTime $mergedAt)
    {
        $this->mergedAt = $mergedAt;
    }

    public static function create(): self
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        return new self($now);
    }

    public static function none(): self
    {
        return new self(self::EMPTY_DATE);
    }

    public static function fromTimestampIfAny(?string $putToReviewAt): self
    {
        if (null === $putToReviewAt) {
            return new self(self::EMPTY_DATE);
        }

        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->setTimestamp((int) $putToReviewAt);

        return new self($date);
    }

    public function toTimestamp(): ?string
    {
        return null !== $this->mergedAt ? (string)$this->mergedAt->getTimestamp() : null;
    }
}
