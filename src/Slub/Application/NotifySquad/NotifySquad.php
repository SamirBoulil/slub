<?php

declare(strict_types=1);

namespace Slub\Application\NotifySquad;

use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Query\GetMessageIdsForPR;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class NotifySquad implements EventSubscriberInterface
{
    public const MESSAGE_PR_GTMED = ':arrow_up: GTMed';

    /** @var GetMessageIdsForPR */
    private $getMessageIdsForPR;

    /** @var ChatClient */
    private $chatClient;

    public function __construct(GetMessageIdsForPR $getMessageIdsForPR, ChatClient $chatClient)
    {
        $this->chatClient = $chatClient;
        $this->getMessageIdsForPR = $getMessageIdsForPR;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PRGTMed::class => 'whenPRHasBeenGTM',
        ];
    }

    public function whenPRHasBeenGTM(PRGTMed $event): void
    {
        $messageIds = $this->getMessageIdsForPR->fetch($event->PRIdentifier());
        foreach ($messageIds as $messageId) {
            $this->chatClient->replyInThread($messageId, self::MESSAGE_PR_GTMED);
        }
    }
}
