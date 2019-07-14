<?php

declare(strict_types=1);

namespace Slub\Domain\Query;

use Slub\Domain\Entity\PR\PRIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
interface GetPRInfoInterface
{
    public function fetch(PRIdentifier $PRIdentifier): PRInfo;
}
