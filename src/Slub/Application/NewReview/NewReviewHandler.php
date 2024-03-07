<?php

declare(strict_types=1);

namespace Slub\Application\NewReview;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Reviewer\ReviewerName;
use Slub\Domain\Query\PRIsInReview;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class NewReviewHandler
{
    public function __construct(
        private PRRepositoryInterface $PRRepository,
        private PRIsInReview $PRIsInReview,
        private LoggerInterface $logger
    ) {
    }

    public function handle(NewReview $review): void
    {
        if ($this->PRIsNotInReview($review)) {
            return;
        }
        $this->updatePRWithReview($review);
        $this->logIt($review);
    }

    private function PRIsNotInReview(NewReview $review): bool
    {
        return !$this->PRIsInReview->fetch(PRIdentifier::create($review->PRIdentifier));
    }

    private function updatePRWithReview(NewReview $review): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::create($review->PRIdentifier));
        $reviewerName = ReviewerName::fromString($review->reviewerName);
        switch ($review->reviewStatus) {
            case 'accepted':
                $PR->GTM($reviewerName);
                break;
            case 'refused':
                $PR->notGTM($reviewerName);
                break;
            case 'commented':
                $PR->comment($reviewerName);
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

    private function logIt(NewReview $review): void
    {
        $logMessage = match ($review->reviewStatus) {
            'accepted' => sprintf('PR "%s" has been GTMed', $review->PRIdentifier),
            'refused' => sprintf('PR "%s" has been NOT GTMed', $review->PRIdentifier),
            'commented' => sprintf('PR "%s" has been commented', $review->PRIdentifier),
            default => throw new \InvalidArgumentException(
                sprintf(
                    'review type "%s" is not supported, supported types are "gtm", "not_gtm", "comment"',
                    $review->reviewStatus
                )
            ),
        };
        // $this->logger->info($logMessage);
    }
}
