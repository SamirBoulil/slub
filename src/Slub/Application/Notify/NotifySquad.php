<?php

declare(strict_types=1);

namespace Slub\Application\Notify;

use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIPending;
use Slub\Domain\Event\CIRed;
use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Event\PRMerged;
use Slub\Domain\Event\PRNotGTMed;
use Slub\Domain\Event\PRPutToReview;
use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class NotifySquad implements EventSubscriberInterface
{
    /** @var string[] */
    public const REACTION_PR_REVIEWED = [
        'zero',
        'one',
        'two',
        'three',
        'four',
        'five',
        'six',
        'seven',
        'eight',
        'nine',
    ];
    public const REACTION_CI_GREEN = 'white_check_mark';
    public const REACTION_CI_RED = 'octagonal_sign';
    public const REACTION_PR_MERGED = 'rocket';
    public const REACTION_CI_PENDING = 'small_orange_diamond';

    /** @var PRRepositoryInterface */
    private $PRRepository;

    /** @var ChatClient */
    private $chatClient;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(PRRepositoryInterface $PRRepository, ChatClient $chatClient, LoggerInterface $logger)
    {
        $this->PRRepository = $PRRepository;
        $this->chatClient = $chatClient;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PRPutToReview::class => 'whenPRIsPutToReview',
            PRGTMed::class       => 'whenPRHasBeenGTMed',
            PRNotGTMed::class    => 'whenPRHasBeenNotGTMed',
            CIGreen::class       => 'whenCIIsGreen',
            CIRed::class         => 'whenCIIsRed',
            CIPending::class     => 'whenCIPending',
            PRMerged::class      => 'whenPRIsMerged',
        ];
    }

    private function synchronizeReactions(PRIdentifier $PRIdentifier): void
    {
        $PR = $this->PRRepository->getBy($PRIdentifier);
        $reactions = $this->getReactionsToSet($PR);
        foreach ($PR->messageIdentifiers() as $messageIdentifier) {
            $this->chatClient->setReactionsToMessageWith($messageIdentifier, $reactions);
        }
        $this->logger->info(
            sprintf(
                'Squad notified for PR %s with reactions: %s',
                $PRIdentifier->stringValue(),
                implode(', ', $reactions)
            )
        );
    }

    /**
     * @return string[]
     */
    private function getReactionsToSet(PR $PR): array
    {
        $normalizedPR = $PR->normalize();

        if ($normalizedPR['IS_MERGED']) {
            return [$this->reactionForPRMerged()];
        }

        return [
            $this->reviewCountReaction($normalizedPR),
            $this->reactionForCIStatus($normalizedPR),
        ];
    }

    private function reactionForCIStatus(array $normalizedPR): string
    {
        $ciStatus = $normalizedPR['CI_STATUS']['BUILD_RESULT'];
        if ('GREEN' === $ciStatus) {
            return self::REACTION_CI_GREEN;
        }
        if ('RED' === $ciStatus) {
            return self::REACTION_CI_RED;
        }

        return self::REACTION_CI_PENDING;
    }

    private function reviewCountReaction(array $normalizedPR): string
    {
        $reviewCount = (int) $normalizedPR['GTMS'];

        return self::REACTION_PR_REVIEWED[$reviewCount];
    }

    public function whenPRIsPutToReview(PRPutToReview $event): void
    {
        $this->synchronizeReactions($event->PRIdentifier());
    }

    public function whenPRHasBeenGTMed(PRGTMed $event): void
    {
        $this->synchronizeReactions($event->PRIdentifier());
    }

    public function whenPRHasBeenNotGTMed(PRNotGTMed $event): void
    {
        $this->synchronizeReactions($event->PRIdentifier());
    }

    public function whenCIIsGreen(CIGreen $event): void
    {
        $this->synchronizeReactions($event->PRIdentifier());
    }

    public function whenCIIsRed(CIRed $event): void
    {
        $this->synchronizeReactions($event->PRIdentifier());
    }

    public function whenCIPending(CIPending $event): void
    {
        $this->synchronizeReactions($event->PRIdentifier());
    }

    public function whenPRIsMerged(PRMerged $event): void
    {
        $this->synchronizeReactions($event->PRIdentifier());
    }

    private function reactionForPRMerged(): string
    {
        return self::REACTION_PR_MERGED;
    }
}
