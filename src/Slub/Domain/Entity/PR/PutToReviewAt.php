<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PutToReviewAt
{
    /** @var \DateTime */
    private $putToReviewAt;

    public function __construct(\DateTime $putToReviewAt)
    {
        $this->putToReviewAt = $putToReviewAt;
    }

    public static function create(): self
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        return  new self($now);
    }

    public static function fromTimestamp(string $putToReviewAt): self
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->setTimestamp((int) $putToReviewAt);

        return new self($date);
    }

    public function toTimestamp(): string
    {
        return (string) $this->putToReviewAt->getTimestamp();
    }

    public function numberOfDaysInReview(): int
    {
        $today = new \DateTime('now', new \DateTimeZone('UTC'));

        $diff = $today->diff($this->putToReviewAt);

        return $diff->days;
    }
}
