<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use Slub\Application\GTMPR\PRGTMedNotify;
use Slub\Domain\Event\PRGTMed;

/**
 * @author Samir Boulil <samir.boulil@akeneo.com>
 */
class PRGTMedSubscriberSpy implements PRGTMedNotify
{
    /** @var bool */
    private $hasBeenCalled = false;

    public function PRGTMed(PRGTMed $PRGTMed): void
    {
        $this->hasBeenCalled = true;
    }

    public function PRhasBeenGMTed(): bool
    {
        return $this->hasBeenCalled;
    }
}
