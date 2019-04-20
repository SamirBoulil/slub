<?php

declare(strict_types=1);

namespace Slub\Application\NewReview;

use Psr\Log\LoggerInterface;
use Slub\Application\NotifySquad\ChatClient;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class NewReviewHandler
{
    public const MESSAGE_PR_GTMED = ':+1: GTM';
    public const MESSAGE_PR_NOT_GTMED = ':woman-gesturing-no: PR Refused';
    public const MESSAGE_PR_COMMENTED = ':lower_left_fountain_pen: PR Commented';

    /** @var PRRepositoryInterface */
    private $PRRepository;

    /** @var IsSupportedInterface */
    private $isSupported;

    /** @var ChatClient */
    private $chatClient;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        IsSupportedInterface $isSupported,
        ChatClient $chatClient,
        LoggerInterface $logger
    ) {
        $this->PRRepository = $PRRepository;
        $this->isSupported = $isSupported;
        $this->chatClient = $chatClient;
        $this->logger = $logger;
    }

    public function handle(NewReview $review)
    {
        if (!$this->isSupported($review)) {
            return;
        }
        $this->updatePRWithReview($review);
        $this->notifySquad($review);
    }

    private function isSupported(NewReview $review): bool
    {
        $repositoryIdentifier = RepositoryIdentifier::fromString($review->repositoryIdentifier);

        return $this->isSupported->repository($repositoryIdentifier);
    }

    private function updatePRWithReview(NewReview $review): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::create($review->PRIdentifier));
        switch ($review->reviewStatus) {
            case 'accepted':
                $PR->GTM();
                break;
            case 'refused':
                $PR->notGTM();
                break;
            case 'commented':
                $PR->comment();
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'review type "%s" is not supported, supported types are "gtm", "not_gtm", "comment"',
                        $review->reviewStatus
                    )
                );
        }
        $this->PRRepository->save($PR);
    }

    private function notifySquad(NewReview $review): void
    {
        switch ($review->reviewStatus) {
            case 'accepted':
                $squadMessage = self::MESSAGE_PR_GTMED;
                $logMessage = 'PR "%s" has been GTMed';
                break;
            case 'refused':
                $squadMessage = self::MESSAGE_PR_NOT_GTMED;
                $logMessage = 'PR "%s" has been NOT GTMed';
                break;
            case 'commented':
                $squadMessage = NewReviewHandler::MESSAGE_PR_COMMENTED;
                $logMessage = 'PR "%s" has been commented';
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'review type "%s" is not supported, supported types are "gtm", "not_gtm", "comment"',
                        $review->reviewStatus
                    )
                );
        }

        $PR = $this->PRRepository->getBy(PRIdentifier::create($review->PRIdentifier));
        $lastMessageIdentifier = last($PR->messageIdentifiers());
        $this->chatClient->replyInThread($lastMessageIdentifier, $squadMessage);
        $this->logger->info(sprintf($logMessage, $review->PRIdentifier));
    }
}
