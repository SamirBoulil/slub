<?php

declare(strict_types=1);

namespace Slub\Application\NewReview;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Entity\Reviewer\ReviewerName;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class NewReviewHandler
{
    /** @var PRRepositoryInterface */
    private $PRRepository;

    /** @var IsSupportedInterface */
    private $isSupported;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        IsSupportedInterface $isSupported,
        LoggerInterface $logger
    ) {
        $this->PRRepository = $PRRepository;
        $this->isSupported = $isSupported;
        $this->logger = $logger;
    }

    public function handle(NewReview $review)
    {
        if (!$this->isSupported($review)) {
            return;
        }
        $this->updatePRWithReview($review);
        $this->logIt($review);
    }

    private function isSupported(NewReview $review): bool
    {
        $repositoryIdentifier = RepositoryIdentifier::fromString($review->repositoryIdentifier);

        return $this->isSupported->repository($repositoryIdentifier);
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
        switch ($review->reviewStatus) {
            case 'accepted':
                $logMessage = sprintf('PR "%s" has been GTMed', $review->PRIdentifier);
                break;
            case 'refused':
                $logMessage = sprintf('PR "%s" has been NOT GTMed', $review->PRIdentifier);
                break;
            case 'commented':
                $logMessage = sprintf('PR "%s" has been commented', $review->PRIdentifier);
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'review type "%s" is not supported, supported types are "gtm", "not_gtm", "comment"',
                        $review->reviewStatus
                    )
                );
        }
        $this->logger->info($logMessage);
    }
}
