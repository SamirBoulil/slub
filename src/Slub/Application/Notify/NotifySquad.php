<?php

declare(strict_types=1);

namespace Slub\Application\Notify;

use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIRed;
use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Event\PRMerged;
use Slub\Domain\Event\PRNotGTMed;
use Slub\Domain\Event\PRPutToReview;
use Slub\Domain\Query\GetMessageIdsForPR;
use Slub\Domain\Query\GetReviewCountForPR;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class NotifySquad implements EventSubscriberInterface
{
    public const REACTION_PR_PUT_TO_REVIEW = 'ok_hand';
    /** @var string[] */
    public const REACTION_PR_REVIEWED = ['zero', 'one',  'two',  'three',  'four',  'five',  'six',  'seven',  'eight',  'nine'];
    public const REACTION_CI_GREEN = 'white_check_mark';
    public const REACTION_CI_RED = 'octagonal_sign';
    public const REACTION_PR_MERGED = 'rocket';

    /** @var GetMessageIdsForPR */
    private $getMessageIdsForPR;

    /** @var GetReviewCountForPR */
    private $getReviewCountForPR;

    /** @var ChatClient */
    private $chatClient;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        GetMessageIdsForPR $getMessageIdsForPR,
        GetReviewCountForPR $getReviewCountForPR,
        ChatClient $chatClient,
        LoggerInterface $logger
    ) {
        $this->chatClient = $chatClient;
        $this->getMessageIdsForPR = $getMessageIdsForPR;
        $this->getReviewCountForPR = $getReviewCountForPR;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PRPutToReview::class => 'whenPRIsPutToReview',
            PRGTMed::class => 'whenPRHasBeenGTMed',
            PRNotGTMed::class => 'whenPRHasBeenNotGTMed',
            CIGreen::class => 'whenCIIsGreen',
            CIRed::class => 'whenCIIsRed',
            PRMerged::class => 'whenPRIsMerged',
        ];
    }

    public function whenPRIsPutToReview(PRPutToReview $event): void
    {
        $this->notifySquadWithReactionForPR($event->PRIdentifier(), self::REACTION_PR_PUT_TO_REVIEW);
        $this->logger->info(sprintf('Squad has been notified pr "%s" is in review', $event->PRIdentifier()->stringValue()));
    }

    public function whenPRHasBeenGTMed(PRGTMed $event): void
    {
        $this->notifyPRHasBeenReviewed($event->PRIdentifier());
    }

    public function whenPRHasBeenNotGTMed(PRNotGTMed $event): void
    {
        $this->notifyPRHasBeenReviewed($event->PRIdentifier());
    }

    public function whenCIIsGreen(CIGreen $event): void
    {
        $this->notifySquadWithReactionForPR($event->PRIdentifier(), self::REACTION_CI_GREEN);
        $this->logger->info(sprintf('Squad has been notified PR "%s" is green', $event->PRIdentifier()->stringValue()));
    }

    public function whenCIIsRed(CIRed $event): void
    {
        $this->notifySquadWithReactionForPR($event->PRIdentifier(), self::REACTION_CI_RED);
        $this->logger->info(sprintf('Squad has been notified PR "%s" is red', $event->PRIdentifier()->stringValue()));
    }

    public function whenPRIsMerged(PRMerged $event): void
    {
        $this->notifySquadWithReactionForPR($event->PRIdentifier(), self::REACTION_PR_MERGED);
        $this->logger->info(sprintf('Squad has been notified PR "%s" is merged', $event->PRIdentifier()->stringValue()));
    }

    private function notifyPRHasBeenReviewed(PRIdentifier $PRIdentifier): void
    {
        $reviewCount = $this->getReviewCountForPR->fetch($PRIdentifier);
        $reviewEmoji = self::REACTION_PR_REVIEWED[$reviewCount] ?? null;
        if (null !== $reviewEmoji) {
            $this->notifySquadWithReactionForPR($PRIdentifier, $reviewEmoji);
        }
        $this->logger->info(sprintf('Squad has been notified PR "%s" has been reviewed', $PRIdentifier->stringValue()));
    }

    private function notifySquadWithReactionForPR(PRIdentifier $PRIdentifier, string $text): void
    {
        $messageIds = $this->getMessageIdsForPR->fetch($PRIdentifier);
        foreach ($messageIds as $messageId) {
            $this->chatClient->reactToMessageWith($messageId, $text);
        }
    }
}
