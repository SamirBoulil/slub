<?php
declare(strict_types=1);

namespace Slub\Application\GTMPR;

use Slub\Domain\Event\PRNotGTMed;

interface PRNotGTMedNotify
{
    public function notifyPRNotGTMed(PRNotGTMed $PRNotGTMed): void;
}
