<?php

declare(strict_types=1);

namespace Slub\Domain\GitHub\Payload;

use Slub\Domain\GitHub\Properties\Repository;

final readonly class Status
{
    private function __construct(
        public string $name,
        public string $sha,
        public Repository $repository,
    ) {
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['name'],
            $payload['sha'],
            Repository::fromNormalized($payload['repository']),
        );
    }
}
