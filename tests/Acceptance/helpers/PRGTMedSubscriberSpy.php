<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use Slub\Domain\Event\PRGTMed;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Samir Boulil <samir.boulil@akeneo.com>
 */
class PRGTMedSubscriberSpy implements EventSubscriberInterface
{
    /** @var bool */
    private $hasBeenCalled = false;

    public static function getSubscribedEvents()
    {
        return [
            PRGTMed::class => 'notifyPRGTMed',
        ];
    }

    public function notifyPRGTMed(PRGTMed $PRGTMed): void
    {
        $this->hasBeenCalled = true;
    }

    public function PRhasBeenGMTed(): bool
    {
        return $this->hasBeenCalled;
    }
}
