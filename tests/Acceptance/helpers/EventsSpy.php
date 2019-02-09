<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIRed;
use Slub\Domain\Event\GTMed;
use Slub\Domain\Event\NotGTMed;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Samir Boulil <samir.boulil@akeneo.com>
 */
class EventsSpy implements EventSubscriberInterface
{
    /** @var bool */
    private $GTMedDispatched = false;

    /** @var bool */
    private $NotGTMedDispatched = false;

    /** @var bool */
    private $CIGreenDispatched = false;

    /** @var bool */
    private $CIRedDispatched = false;

    /** @var array */
    public $events;

    public static function getSubscribedEvents()
    {
        return [
            GTMed::class    => 'notifyPRGTMed',
            NotGTMed::class => 'notifyPRNotGTMed',
            CIGreen::class  => 'notifyCIGreen',
            CIRed::class    => 'notifyCIRed',
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
        $this->NotGTMedDispatched = true;
    }

    public function PRNotGMTedDispatched(): bool
    {
        return $this->NotGTMedDispatched;
    }

    public function notifyCIGreen(CIGreen $CIGreen): void
    {
        $this->CIGreenDispatched = true;
    }

    public function CIGreenEventDispatched(): bool
    {
        return $this->CIGreenDispatched;
    }

    public function notifyCIRed(CIRed $CIRed): void
    {
        $this->CIRedDispatched = true;
    }

    public function CIRedEventDispatched(): bool
    {
        return $this->CIRedDispatched;
    }

    public function hasEvents(): bool
    {
        return $this->GTMedDispatched || $this->NotGTMedDispatched || $this->CIGreenDispatched || $this->CIRedDispatched;
    }
}
