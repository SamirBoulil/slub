<?php

declare(strict_types=1);

namespace Slub\Application\NotifySquad;

use Psr\Log\LoggerInterface;
use Slub\Application\PutPRToReview\PutPRToReview;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIRed;
use Slub\Domain\Event\PRCommented;
use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Event\PRMerged;
use Slub\Domain\Event\PRNotGTMed;
use Slub\Domain\Event\PRPutToReview;
use Slub\Domain\Query\GetMessageIdsForPR;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class NotifySquad implements EventSubscriberInterface
{
    public const REACTION_PR_PUT_TO_REVIEW = 'ok_hand';
    public const REACTION_PR_MERGED = 'rocket';

    public const MESSAGE_PR_GTMED = ':+1: GTM';
    public const MESSAGE_PR_NOT_GTMED = ':woman-gesturing-no: PR Refused';
    public const MESSAGE_PR_COMMENTED = ':lower_left_fountain_pen: PR Commented';
    public const MESSAGE_CI_GREEN = ':white_check_mark: CI OK';
    public const MESSAGE_CI_RED = ':octagonal_sign: CI Failed';

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
            PRPutToReview::class => 'whenPRIsPutToReview',
            PRGTMed::class => 'whenPRHasBeenGTM',
            PRNotGTMed::class => 'whenPRHasBeenNotGTM',
            PRCommented::class => 'whenPRComment',
            CIGreen::class => 'whenCIIsGreen',
            CIRed::class => 'whenCIIsRed',
            PRMerged::class => 'whenPRIsMerged'
        ];
    }

    public function whenPRHasBeenGTM(PRGTMed $event): void
    {
        $this->replyInThreads($event->PRIdentifier(), self::MESSAGE_PR_GTMED);
        $this->logger->info(
            sprintf(
                'Squad has been notified PR "%s" has been GTMed',
                $event->PRIdentifier()->stringValue()
            )
        );
    }

    public function whenPRHasBeenNotGTM(PRNotGTMed $event): void
    {
        $this->replyInThreads($event->PRIdentifier(), self::MESSAGE_PR_NOT_GTMED);
        $this->logger->info(
            sprintf(
                'Squad has been notified PR "%s" has been NOT GTMed',
                $event->PRIdentifier()->stringValue()
            )
        );
    }

    public function whenPRIsPutToReview(PRPutToReview $event): void
    {
        $this->chatClient->reactToMessageWith($event->messageIdentifier(), self::REACTION_PR_PUT_TO_REVIEW);
        $this->logger->info(
            sprintf(
                'squad has been notified pr "%s" is in review',
                $event->PRIdentifier()->stringValue()
            )
        );
    }

    public function whenPRComment(PRCommented $event): void
    {
        $this->replyInThreads($event->PRIdentifier(), self::MESSAGE_PR_COMMENTED);
        $this->logger->info(
            sprintf(
                'Squad has been notified PR "%s" has been commented',
                $event->PRIdentifier()->stringValue()
            )
        );
    }

    public function whenCIIsGreen(CIGreen $event): void
    {
        $this->replyInThreads($event->PRIdentifier(), self::MESSAGE_CI_GREEN);
        $this->logger->info(
            sprintf(
                'Squad has been notified PR "%s" has a CI Green',
                $event->PRIdentifier()->stringValue()
            )
        );
    }

    public function whenCIIsRed(CIRed $event): void
    {
        $this->replyInThreads($event->PRIdentifier(), self::MESSAGE_CI_RED);
        $this->logger->info(
            sprintf(
                'Squad has been notified PR "%s" has a CI Red',
                $event->PRIdentifier()->stringValue()
            )
        );
    }

    public function whenPRIsMerged(PRMerged $event): void
    {
        $messageIds = $this->getMessageIdsForPR->fetch($event->PRIdentifier());
        foreach ($messageIds as $messageId) {
            $this->chatClient->reactToMessageWith($messageId, self::REACTION_PR_MERGED);
        }
        $this->logger->info(
            sprintf(
                'Squad has been notified PR "%s" is merged',
                $event->PRIdentifier()->stringValue()
            )
        );
    }

    private function replyInThreads(PRIdentifier $PRIdentifier, string $message): void
    {
        $messageIds = $this->getMessageIdsForPR->fetch($PRIdentifier);
        $lastMessageId = last($messageIds);
        $this->chatClient->replyInThread($lastMessageId, $message);
    }
}
