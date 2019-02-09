<?php

declare(strict_types=1);

namespace Slub\Application\Review;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class ReviewHandler
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

    public function handle(Review $review)
    {
        if ($this->isUnsupported($review)) {
            return;
        }

        $PR = $this->PRRepository->getBy(PRIdentifier::create($review->PRIdentifier));
        if ($review->isGTM) {
            $PR->GTM();
        } else {
            $PR->notGTM();
        }
        $this->PRRepository->save($PR);
    }

    private function isUnsupported(Review $review): bool
    {
        $repositoryIdentifier = RepositoryIdentifier::fromString($review->repositoryIdentifier);

        return $this->isSupported->repository($repositoryIdentifier) === false;
    }
}
