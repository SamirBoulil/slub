<?php

declare(strict_types=1);

namespace Slub\Domain\Repository;

use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;

interface PRRepositoryInterface
{
    public function save(PR $pr): void;

    /**
     * @throws PRNotFoundException
     */
    public function getBy(PRIdentifier $PRidentifier): PR;
}
