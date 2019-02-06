<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use Slub\Application\GTMPR\PRNotGTMedNotify;
use Slub\Domain\Event\PRNotGTMed;

/**
 * @author Samir Boulil <samir.boulil@akeneo.com>
 */
class PRNotGTMedSubscriberSpy implements PRNotGTMedNotify
{
    /** @var bool */
    private $hasBeenCalled = false;

    public function notifyPRNotGTMed(PRNotGTMed $PRNotGTMed): void
    {
        $this->hasBeenCalled = true;
    }

    public function PRhasNotBeenGMTed(): bool
    {
        return $this->hasBeenCalled;
    }
}
