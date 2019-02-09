<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Event\PRNotGTMed;
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

    public static function getSubscribedEvents()
    {
        return [
            PRGTMed::class    => 'notifyPRGTMed',
            PRNotGTMed::class => 'notifyPRNotGTMed',
            CIGreen::class    => 'notifyCIGreen',
        ];
    }

    public function notifyPRGTMed(PRGTMed $PRGTMed): void
    {
        $this->GTMedDispatched = true;
    }

    public function PRGMTedDispatched(): bool
    {
        return $this->GTMedDispatched;
    }

    public function notifyPRNotGTMed(PRNotGTMed $PRNotGTMed): void
    {
        $this->NotGTMedDispatched = true;
    }

    public function PRNotGMTedDispatched(): bool
    {
        return $this->NotGTMedDispatched;
    }

    public function notifyCIGreen()
    {
        $this->CIGreenDispatched = true;
    }

    public function CIGreenEventDispatched(): bool
    {
        return $this->CIGreenDispatched;
    }

    public function hasEvents(): bool
    {
        return $this->GTMedDispatched || $this->NotGTMedDispatched || $this->CIGreenDispatched;
    }
}
