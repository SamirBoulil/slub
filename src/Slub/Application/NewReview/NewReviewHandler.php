<?php

declare(strict_types=1);

namespace Slub\Application\NewReview;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
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
        if ($this->isUnsupported($review)) {
            return;
        }
        $this->updatePRWithReview($review);
    }

    private function isUnsupported(NewReview $review): bool
    {
        $repositoryIdentifier = RepositoryIdentifier::fromString($review->repositoryIdentifier);

        return $this->isSupported->repository($repositoryIdentifier) === false;
    }

    private function updatePRWithReview(NewReview $review): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::create($review->PRIdentifier));
        switch ($review->reviewStatus) {
            case 'accepted':
                $PR->GTM();
                $logMessage = 'PR "%s" has been GTMed';
                break;
            case 'refused':
                $PR->notGTM();
                $logMessage = 'PR "%s" has been NOT GTMed';
                break;
            case 'commented':
                $PR->comment();
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
        $this->PRRepository->save($PR);
        $this->logger->info(sprintf($logMessage, $review->PRIdentifier));
    }
}
