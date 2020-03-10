<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIPending;
use Slub\Domain\Event\CIRed;
use Slub\Domain\Event\GoodToMerge;
use Slub\Domain\Event\PRClosed;
use Slub\Domain\Event\PRCommented;
use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Event\PRMerged;
use Slub\Domain\Event\PRNotGTMed;
use Slub\Domain\Event\PRPutToReview;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class EventsSpy implements EventSubscriberInterface
{
    /** @var array */
    public $events = [];

    public static function getSubscribedEvents(): array
    {
        return [
            PRPutToReview::class => 'notifyPRPutToReview',
            PRGTMed::class => 'notifyPRGTMed',
            PRNotGTMed::class => 'notifyPRNotGTMed',
            PRCommented::class => 'notifyPRCommented',
            CIGreen::class => 'notifyCIGreen',
            CIRed::class => 'notifyCIRed',
            CIPending::class => 'notifyCIPending',
            PRMerged::class => 'notifyPRMerged',
            PRClosed::class => 'notifyPRClosed',
            GoodToMerge::class => 'notifyPRGoodToMerge',
        ];
    }

    public function notifyPRPutToReview(PRPutToReview $PRPutToReview): void
    {
        $this->events[PRPutToReview::class] = true;
    }

    public function PRPutToReviewDispatched(): bool
    {
        return $this->events[PRPutToReview::class] ?? false;
    }

    public function notifyPRGTMed(PRGTMed $PRGTMed): void
    {
        $this->events[PRGTMed::class] = true;
    }

    public function PRGMTedDispatched(): bool
    {
        return $this->events[PRGTMed::class] ?? false;
    }

    public function notifyPRNotGTMed(PRNotGTMed $PRNotGTMed): void
    {
        $this->events[PRNotGTMed::class] = true;
    }

    public function PRNotGMTedDispatched(): bool
    {
        return $this->events[PRNotGTMed::class] ?? false;
    }

    public function notifyPRCommented(PRCommented $PRCommented): void
    {
        $this->events[PRCommented::class] = true;
    }

    public function PRCommentedDispatched(): bool
    {
        return $this->events[PRCommented::class] ?? false;
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

    public function notifyCIPending(CIPending $CIPending)
    {
        $this->events[get_class($CIPending)] = true;
    }

    public function CIPendingEventDispatched(): bool
    {
        return $this->events[CIPending::class] ?? false;
    }

    public function notifyPRMerged(PRMerged $merged): void
    {
        $this->events[PRMerged::class] = true;
    }

    public function notifyPRClosed(PRClosed $closed): void
    {
        $this->events[PRClosed::class] = true;
    }

    public function PRMergedDispatched(): bool
    {
        return $this->events[PRMerged::class] ?? false;
    }

    public function PRClosedDispatched(): bool
    {
        return $this->events[PRClosed::class] ?? false;
    }

    public function notifyPRGoodToMerge(GoodToMerge $goodToMerge): void
    {
        $this->events[GoodToMerge::class] = true;
    }

    public function PRGoodToMergeDispatched(): bool
    {
        return $this->events[GoodToMerge::class] ?? false;
    }

    public function hasEvents(): bool
    {
        return !empty($this->events);
    }
}
