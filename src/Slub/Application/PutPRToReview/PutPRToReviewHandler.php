<?php

declare(strict_types=1);

namespace Slub\Application\PutPRToReview;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;

class PutPRToReviewHandler
{
    /** @var PRRepositoryInterface */
    private $PRRepository;

    /** @var IsSupportedInterface */
    private $isSupported;

    public function __construct(PRRepositoryInterface $PRRepository, IsSupportedInterface $isRepositorySupported)
    {
        $this->PRRepository = $PRRepository;
        $this->isSupported = $isRepositorySupported;
    }

    public function handle(PutPRToReview $command)
    {
        if ($this->isUnsupported($command)) {
            return;
        }
        $this->createPr($command);
    }

    private function isUnsupported(PutPRToReview $putPRToReview): bool
    {
        $repositoryIdentifier = RepositoryIdentifier::fromString($putPRToReview->repositoryIdentifier);
        $channelIdentifier = ChannelIdentifier::fromString($putPRToReview->channelIdentifier);

        return !$this->isSupported->repository($repositoryIdentifier)
            || !$this->isSupported->channel($channelIdentifier);
    }

    private function createPr(PutPRToReview $putPRToReview): void
    {
        $this->PRRepository->save(
            PR::create(PRIdentifier::create($putPRToReview->PRIdentifier))
        );
    }
}
