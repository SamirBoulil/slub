<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class MergedAt
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';
    private const EMPTY_DATE = '';

    /** @var string */
    private $putToReviewAt;

    public function __construct(?string $putToReviewAt)
    {
        $this->putToReviewAt = $putToReviewAt;
    }

    public static function create(): self
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        return new self($now->format(self::DATE_FORMAT));
    }

    public static function none(): self
    {
        return new self(self::EMPTY_DATE);
    }

    public static function fromString(string $putToReviewAt): self
    {
        return new self($putToReviewAt);
    }

    public function stringValue(): string
    {
        return $this->putToReviewAt;
    }
}
