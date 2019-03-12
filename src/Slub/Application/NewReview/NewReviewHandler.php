<?php

declare(strict_types=1);

namespace Slub\Application\NewReview;

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

    public function __construct(
        PRRepositoryInterface $PRRepository,
        IsSupportedInterface $isSupported
    ) {
        $this->PRRepository = $PRRepository;
        $this->isSupported = $isSupported;
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
            case 'gtm':
                $PR->GTM();
                break;
            case 'not_gtm':
                $PR->notGTM();
                break;
            case 'comment':
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
}
