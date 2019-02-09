<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Event\PRNotGTMed;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Samir Boulil <samir.boulil@akeneo.com>
 */
class EventsSpy implements EventSubscriberInterface
{
    /** @var bool */
    private $hasBeenGTMed = false;
    private $hasNotBeenGTMed = false;

    public static function getSubscribedEvents()
    {
        return [
            PRGTMed::class => 'notifyPRGTMed',
            PRNotGTMed::class => 'notifyPRNotGTMed',
        ];
    }

    public function notifyPRGTMed(PRGTMed $PRGTMed): void
    {
        $this->hasBeenGTMed = true;
    }

    public function PRhasBeenGMTed(): bool
    {
        return $this->hasBeenGTMed;
    }

    public function notifyPRNotGTMed(PRNotGTMed $PRNotGTMed): void
    {
        $this->hasNotBeenGTMed = true;
    }

    public function PRhasNotBeenGMTed(): bool
    {
        return $this->hasNotBeenGTMed;
    }

    public function hasEvents(): bool
    {
        return $this->hasBeenGTMed || $this->hasNotBeenGTMed;
    }
}
