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
    private $prRepository;

    /** @var IsSupportedInterface */
    private $isSupported;

    public function __construct(PRRepositoryInterface $prRepository, IsSupportedInterface $isRepositorySupported)
    {
        $this->prRepository = $prRepository;
        $this->isSupported = $isRepositorySupported;
    }

    public function handle(PutPRToReview $command)
    {
        if ($this->isUnsupported($command)) {
            return;
        }
        $this->createPr($command);
    }

    private function isUnsupported(PutPRToReview $command):bool
    {
        $repositoryIdentifier = RepositoryIdentifier::fromString($command->repository);
        $channelIdentifier = ChannelIdentifier::fromString($command->channel);

        return !$this->isSupported->repository($repositoryIdentifier)
            || !$this->isSupported->channel($channelIdentifier);
    }

    private function createPr(PutPRToReview $putPRToReview): void
    {
        $pr = PR::create(
            PRIdentifier::create($putPRToReview->repository, $putPRToReview->prIdentifier)
        );
        $this->prRepository->save($pr);
    }
}
