<?php

declare(strict_types=1);

namespace Slub\Application\GTMPR;

use Slub\Domain\Event\PRNotGTMed;
use Webmozart\Assert\Assert;

/**
 * @author Samir Boulil <samir.boulil@akeneo.com>
 */
class PRNotGTMedNotifyMany implements PRNotGTMedNotify
{
    /** @var PRNotGTMedNotify[] */
    private $subscribers;

    public function __construct(array $subscribers)
    {
        Assert::allIsInstanceOf($subscribers, PRNotGTMedNotify::class);
        $this->subscribers = $subscribers;
    }

    public function notifyPRNotGTMed(PRNotGTMed $PRNotGTMed): void
    {
        array_map(
            function (PRNotGTMedNotify $subscriber) use ($PRNotGTMed) {
                $subscriber->notifyPRNotGTMed($PRNotGTMed);
            },
            $this->subscribers
        );
    }
}
