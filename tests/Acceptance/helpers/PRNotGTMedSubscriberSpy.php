<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use Slub\Application\GTMPR\PRNotGTMedNotify;
use Slub\Domain\Event\PRNotGTMed;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Samir Boulil <samir.boulil@akeneo.com>
 */
class PRNotGTMedSubscriberSpy implements PRNotGTMedNotify, EventSubscriberInterface
{
    /** @var bool */
    private $hasBeenCalled = false;

    public static function getSubscribedEvents()
    {
        return [
            PRNotGTMed::class => 'notifyPRNotGTMed'
        ];
    }

    public function notifyPRNotGTMed(PRNotGTMed $PRNotGTMed): void
    {
        $this->hasBeenCalled = true;
    }

    public function PRhasNotBeenGMTed(): bool
    {
        return $this->hasBeenCalled;
    }
}
