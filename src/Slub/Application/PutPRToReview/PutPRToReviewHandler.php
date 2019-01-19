<?php

declare(strict_types=1);

namespace Slub\Application\PutPRToReview;

use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;

class PutPRToReviewHandler
{
    /** @var PRRepositoryInterface */
    private $prRepository;

    /** @var IsSupportedInterface */
    private $isSupported;

    public function __construct(PRRepositoryInterface $prRepository, IsSupportedInterface $isRepositorySupported)
    {
        $this->prRepository = $prRepository;
        $this->isSupported = $isRepositorySupported;
    }

    public function handle(PutPRToReview $putPRToReview)
    {
        if ($this->isUnsupported($putPRToReview)) {
            return;
        }

        $pr = PR::create(
            PRIdentifier::create($putPRToReview->repository, $putPRToReview->pullRequest)
        );
        $this->prRepository->save($pr);
    }

    private function isUnsupported(PutPRToReview $putPRToReview):bool
    {
        $repositoryIdentifier = RepositoryIdentifier::fromString($putPRToReview->repository);

        return !$this->isSupported->repository($repositoryIdentifier);
    }
}
