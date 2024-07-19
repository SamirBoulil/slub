<?php

declare(strict_types=1);

namespace Slub\Domain\GitHub\Properties;

final readonly class Repository
{
    private function __construct(
        public string $fullName
    ) {
    }

    public static function fromNormalized(array $normalized): self
    {
        return new self($normalized['full_name']);
    }
}
