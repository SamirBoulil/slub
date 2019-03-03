<?php
declare(strict_types=1);

namespace Slub\Domain\Query;

use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PRIdentifier;

interface GetMessageIdsForPR
{
    /** @return MessageIdentifier[] */
    public function fetch(PRIdentifier $PRIdentifier): array;
}
