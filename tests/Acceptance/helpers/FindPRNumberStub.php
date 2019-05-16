<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class FindPRNumberStub
{
    /** @var string|null */
    private $stub;

    public function fetch(string $repository, string $commitRef): ?string
    {
        return $this->stub;
    }

    public function stubWith(?string $stub): void
    {
        $this->stub = $stub;
    }
}
