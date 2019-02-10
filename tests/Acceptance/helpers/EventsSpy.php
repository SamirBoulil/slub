<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIRed;
use Slub\Domain\Event\GTMed;
use Slub\Domain\Event\Merged;
use Slub\Domain\Event\NotGTMed;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Samir Boulil <samir.boulil@akeneo.com>
 */
class EventsSpy implements EventSubscriberInterface
{
    /** @var array */
    public $events = [];

    public static function getSubscribedEvents(): array
    {
        return [
            GTMed::class    => 'notifyPRGTMed',
            NotGTMed::class => 'notifyPRNotGTMed',
            CIGreen::class  => 'notifyCIGreen',
            CIRed::class    => 'notifyCIRed',
            Merged::class   => 'notifyPRMerged',
        ];
    }

    public function notifyPRGTMed(GTMed $PRGTMed): void
    {
        $this->events[GTMed::class] = true;
    }

    public function PRGMTedDispatched(): bool
    {
        return $this->events[GTMed::class] ?? false;
    }

    public function notifyPRNotGTMed(NotGTMed $PRNotGTMed): void
    {
        $this->events[NotGTMed::class] = true;
    }

    public function PRNotGMTedDispatched(): bool
    {
        return $this->events[NotGTMed::class] ?? false;
    }

    public function notifyCIGreen(CIGreen $CIGreen): void
    {
        $this->events[CIGreen::class] = true;
    }

    public function CIGreenEventDispatched(): bool
    {
        return $this->events[CIGreen::class] ?? false;
    }

    public function notifyCIRed(CIRed $CIRed): void
    {
        $this->events[CIRed::class] = true;
    }

    public function CIRedEventDispatched(): bool
    {
        return $this->events[CIRed::class] ?? false;
    }

    public function notifyPRMerged(Merged $merged): void
    {
        $this->events[Merged::class] = true;
    }

    public function PRMergedDispatched(): bool
    {
        return $this->events[Merged::class] ?? false;
    }

    public function hasEvents(): bool
    {
        return !empty($this->events);
    }
}
