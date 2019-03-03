<?php
declare(strict_types=1);

namespace Slub\Domain\Query;

use Slub\Domain\Entity\PR\MessageId;
use Slub\Domain\Entity\PR\PRIdentifier;

interface GetMessageIdsForPR
{
    /** @return MessageId[] */
    public function fetch(PRIdentifier $PRIdentifier): array;
}
