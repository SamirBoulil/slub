<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use Slub\Infrastructure\VCS\Github\Query\FindPRNumberInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class FindPRNumberDummy implements FindPRNumberInterface
{
    public function fetch(string $repository, string $commitRef): ?string
    {
        return '10';
    }
}
