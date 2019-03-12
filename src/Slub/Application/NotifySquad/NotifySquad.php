<?php

declare(strict_types=1);

namespace Slub\Application\NotifySquad;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\PRCommented;
use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Event\PRNotGTMed;
use Slub\Domain\Query\GetMessageIdsForPR;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class NotifySquad implements EventSubscriberInterface
{
    public const MESSAGE_PR_GTMED = ':arrow_up: GTMed';
    public const MESSAGE_PR_NOT_GTMED = ':arrow_up: PR Not GTMed';
    public const MESSAGE_PR_COMMENTED = ':arrow_up: PR Commented';

    /** @var GetMessageIdsForPR */
    private $getMessageIdsForPR;

    /** @var ChatClient */
    private $chatClient;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        GetMessageIdsForPR $getMessageIdsForPR,
        ChatClient $chatClient,
        LoggerInterface $logger
    ) {
        $this->chatClient = $chatClient;
        $this->getMessageIdsForPR = $getMessageIdsForPR;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PRGTMed::class     => 'whenPRHasBeenGTM',
            PRNotGTMed::class  => 'whenPRHasBeenNotGTM',
            PRCommented::class => 'whenPRComment',
        ];
    }

    public function whenPRHasBeenGTM(PRGTMed $event): void
    {
        $text = self::MESSAGE_PR_GTMED;
        $PRIdentifier = $event->PRIdentifier();
        $this->replyInThreads($PRIdentifier, $text);
    }

    public function whenPRHasBeenNotGTM(PRNotGTMed $event): void
    {
        $text = self::MESSAGE_PR_NOT_GTMED;
        $PRIdentifier = $event->PRIdentifier();
        $this->replyInThreads($PRIdentifier, $text);
    }

    public function whenPRComment(PRCommented $event): void
    {
        $text = self::MESSAGE_PR_COMMENTED;
        $PRIdentifier = $event->PRIdentifier();
        $this->replyInThreads($PRIdentifier, $text);
    }

    private function replyInThreads(PRIdentifier $PRIdentifier, string $message): void
    {
        $messageIds = $this->getMessageIdsForPR->fetch($PRIdentifier);
        $lastMessageId = last($messageIds);
        $this->chatClient->replyInThread($lastMessageId, $message);
        $this->logger->critical(
            sprintf(
                'Notified the squad a PR has been GTMed: %s',
                implode(
                    ',',
                    array_map(function (MessageIdentifier $messageId) {
                        return $messageId->stringValue();
                    }, $messageIds)
                )
            )
        );
    }
}
