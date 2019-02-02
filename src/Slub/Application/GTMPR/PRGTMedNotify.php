<?php
declare(strict_types=1);

namespace Slub\Application\GTMPR;

use Slub\Domain\Event\PRGTMed;

interface PRGTMedNotify
{
    public function PRGTMed(PRGTMed $PRGTMed): void;
}
