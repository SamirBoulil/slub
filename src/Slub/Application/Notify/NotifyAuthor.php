<?php

declare(strict_types=1);

namespace Slub\Application\Notify;

use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIRed;
use Slub\Domain\Event\GoodToMerge;
use Slub\Domain\Event\PRCommented;
use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Event\PRNotGTMed;
use Slub\Domain\Query\GetMessageIdsForPR;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class NotifyAuthor implements EventSubscriberInterface
{
    public const PLACEHOLDER_BUILD_LINK = '{{build_link}}';
    public const PLACEHOLDER_REVIEWER_NAME = '{{reviewer_name}}';
    public const PLACEHOLDER_PR_LINK = '{{pr_link}}';
    public const MESSAGE_PR_GTMED = ':+1: ' . self::PLACEHOLDER_REVIEWER_NAME . ' GTMed';
    public const MESSAGE_PR_NOT_GTMED = ':woman-gesturing-no: ' . self::PLACEHOLDER_REVIEWER_NAME . ' refused';
    public const MESSAGE_PR_COMMENTED = ':lower_left_fountain_pen: ' . self::PLACEHOLDER_REVIEWER_NAME . ' commented';
    public const MESSAGE_CI_GREEN = ':white_check_mark: CI OK';
    public const MESSAGE_CI_RED = ':octagonal_sign: CI Failed <' . self::PLACEHOLDER_BUILD_LINK . '|(see build results)>';
    public const MESSAGE_GOOD_TO_MERGE = ':clap: Congratz <' . self::PLACEHOLDER_PR_LINK . '|Your PR> is good to merge! ';

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
            CIGreen::class     => 'whenCIIsGreen',
            CIRed::class       => 'whenCIIsRed',
            GoodToMerge::class => 'whenGoodToMerge'
        ];
    }

    public function whenPRHasBeenGTM(PRGTMed $event): void
    {
        $gtmedMessage = str_replace(
            self::PLACEHOLDER_REVIEWER_NAME,
            $event->reviewerName()->stringValue(),
            self::MESSAGE_PR_GTMED
        );
        $this->replyInThread($event->PRIdentifier(), $gtmedMessage);
        $this->logger->info(
            sprintf('Author has been notified PR "%s" has been GTMed', $event->PRIdentifier()->stringValue())
        );
    }

    public function whenPRHasBeenNotGTM(PRNotGTMed $event): void
    {
        $notGtmedMessage = str_replace(
            self::PLACEHOLDER_REVIEWER_NAME,
            $event->reviewerName()->stringValue(),
            self::MESSAGE_PR_NOT_GTMED
        );
        $this->replyInThread($event->PRIdentifier(), $notGtmedMessage);
        $this->logger->info(
            sprintf('Author has been notified PR "%s" has been NOT GTMed', $event->PRIdentifier()->stringValue())
        );
    }

    public function whenPRComment(PRCommented $event): void
    {
        $commentedMessage = str_replace(
            self::PLACEHOLDER_REVIEWER_NAME,
            $event->reviewerName()->stringValue(),
            self::MESSAGE_PR_COMMENTED
        );
        $this->replyInThread($event->PRIdentifier(), $commentedMessage);
        $this->logger->info(
            sprintf('Author has been notified PR "%s" has been commented', $event->PRIdentifier()->stringValue())
        );
    }

    public function whenCIIsGreen(CIGreen $event): void
    {
        $this->replyInThread($event->PRIdentifier(), self::MESSAGE_CI_GREEN);
        $this->logger->info(
            sprintf('Author has been notified PR "%s" has a CI Green', $event->PRIdentifier()->stringValue())
        );
    }

    public function whenCIIsRed(CIRed $event): void
    {
        $redCIMessage = str_replace(
            self::PLACEHOLDER_BUILD_LINK,
            $event->buildLink()->stringValue(),
            self::MESSAGE_CI_RED
        );
        $this->replyInThread($event->PRIdentifier(), $redCIMessage);
        $this->logger->info(
            sprintf('Author has been notified PR "%s" has a CI Red', $event->PRIdentifier()->stringValue())
        );
    }

    public function whenGoodToMerge(GoodToMerge $event): void
    {
        preg_match('#(.+)\/(.+)\/(.+)#', $event->PRIdentifier()->stringValue(), $matches);
        $PRLink = sprintf('https://github.com/%s/%s/pull/%s', $matches[1], $matches[2], $matches[3]);
        $goodToMergeMessage = str_replace(self::PLACEHOLDER_PR_LINK, $PRLink, self::MESSAGE_GOOD_TO_MERGE);
        $this->replyInThread($event->PRIdentifier(), $goodToMergeMessage);
    }

    private function replyInThread(PRIdentifier $PRIdentifier, string $message): void
    {
        $messageIds = $this->getMessageIdsForPR->fetch($PRIdentifier);
        $lastMessageId = last($messageIds);
        $this->chatClient->replyInThread($lastMessageId, $message);
    }
}
