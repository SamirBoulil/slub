<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\PR;

use Webmozart\Assert\Assert;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class CIStatus
{
    private const NO_STATUS = 'NO_STATUS';
    private const GREEN = 'GREEN';
    private const RED = 'RED';

    /** @var string */
    private $status;

    private function __construct(string $status)
    {
        $this->status = $status;
    }

    public static function noStatus(): self
    {
        return new self(self::NO_STATUS);
    }

    public static function green(): self
    {
        return new self(self::GREEN);
    }

    public static function red(): self
    {
        return new self(self::RED);
    }

    public static function fromNormalized(string $ciStatus): self
    {
        Assert::oneOf($ciStatus, [self::NO_STATUS, self::GREEN, self::RED]);

        return new self($ciStatus);
    }

    public function stringValue(): string
    {
        return $this->status;
    }

    public function isGreen(): bool
    {
        return $this->status === self::GREEN;
    }
}
