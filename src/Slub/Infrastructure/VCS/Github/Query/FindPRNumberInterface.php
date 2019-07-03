<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
interface FindPRNumberInterface
{
    public function fetch(string $repository, string $commitRef): ?string;
}
