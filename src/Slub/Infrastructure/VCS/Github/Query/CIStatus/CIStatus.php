<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\CIStatus;

use ConvenientImmutability\Immutable;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CIStatus
{
    private const GREEN = 'GREEN';
    private const PENDING = 'PENDING';
    private const RED = 'RED';

    use Immutable;

    private function __construct(public string $name, public string $status, public string $buildLink = '')
    {
    }

    public static function green(string $name = ''): self
    {
        return new self($name, self::GREEN, '');
    }

    public static function pending(string $name = ''): self
    {
        return new self($name, self::PENDING, '');
    }

    public static function red(string $name = '', string $buildLink = ''): self
    {
        return new self($name, self::RED, $buildLink);
    }

    public function isGreen(): bool
    {
        return self::GREEN === $this->status;
    }

    public function isRed(): bool
    {
        return self::RED === $this->status;
    }

    public function isPending(): bool
    {
        return self::PENDING === $this->status;
    }
}
