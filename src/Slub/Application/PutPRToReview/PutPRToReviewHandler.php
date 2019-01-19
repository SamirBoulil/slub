<?php

declare(strict_types=1);

namespace Slub\Application\PutPRToReview;

use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;

class PutPRToReviewHandler
{
    /** @var PRRepositoryInterface */
    private $prRepository;

    public function __construct(PRRepositoryInterface $prRepository)
    {
        $this->prRepository = $prRepository;
    }

    public function handle(PutPRToReview $putPRToReview)
    {
        $pr = PR::create(
            PRIdentifier::create($putPRToReview->organization, $putPRToReview->repository, $putPRToReview->pullRequest)
        );
        $this->prRepository->save($pr);
    }
}
