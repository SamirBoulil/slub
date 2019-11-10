<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ClosedAt
{
    private const EMPTY_DATE = null;

    /** @var ?\DateTime */
    private $closedAt;

    public function __construct(?\DateTime $closedAt)
    {
        $this->closedAt = $closedAt;
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

    public static function fromTimestampIfAny(?string $closedAtTimestamp): self
    {
        if (null === $closedAtTimestamp) {
            return new self(self::EMPTY_DATE);
        }

        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->setTimestamp((int) $closedAtTimestamp);

        return new self($date);
    }

    public function toTimestamp(): ?string
    {
        return null !== $this->closedAt ? (string)$this->closedAt->getTimestamp() : null;
    }
}
