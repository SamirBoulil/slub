<?php

declare(strict_types=1);

namespace Slub\Application\GTMPR;

use Slub\Domain\Event\PRGTMed;
use Webmozart\Assert\Assert;

/**
 * @author Samir Boulil <samir.boulil@akeneo.com>
 */
class PRGTMedNotifyMany implements PRGTMedNotify
{
    /** @var PRGTMedNotify[] $subscribers */
    private $subscribers;

    public function __construct(array $subscribers)
    {
        Assert::allIsInstanceOf($subscribers, PRGTMedNotify::class);
        $this->subscribers = $subscribers;
    }

    public function notifyPRGTMed(PRGTMed $PRGTMed):void
    {
        array_map(
            function (PRGTMedNotify $subscriber) use ($PRGTMed) {
                $subscriber->notifyPRGTMed($PRGTMed);
            },
            $this->subscribers
        );
    }
}
