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
        $this->checkIsSupported($putPRToReview);
        $pr = PR::create(
            PRIdentifier::create($putPRToReview->repository, $putPRToReview->pullRequest)
        );
        $this->prRepository->save($pr);
    }

    private function checkIsSupported(PutPRToReview $putPRToReview): void
    {
        $repositoryIdentifier = RepositoryIdentifier::fromString($putPRToReview->repository);
        if (!$this->isSupported->repository($repositoryIdentifier)) {
            throw new \Exception('Unsupported repository');
        }
    }
}
