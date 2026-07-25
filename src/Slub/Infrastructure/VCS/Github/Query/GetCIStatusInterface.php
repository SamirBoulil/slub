<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CIStatus;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
interface GetCIStatusInterface
{
    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): CIStatus;
}
