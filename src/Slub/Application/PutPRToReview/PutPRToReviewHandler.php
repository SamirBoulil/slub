<?php

declare(strict_types=1);

namespace Slub\Application\PutPRToReview;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Domain\Repository\PRRepositoryInterface;

class PutPRToReviewHandler
{
    /** @var PRRepositoryInterface */
    private $PRRepository;

    /** @var IsSupportedInterface */
    private $isSupported;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        IsSupportedInterface $isRepositorySupported,
        LoggerInterface $logger
    ) {
        $this->PRRepository = $PRRepository;
        $this->isSupported = $isRepositorySupported;
        $this->logger = $logger;
    }

    public function handle(PutPRToReview $command)
    {
        if (!$this->isSupported($command)) {
            return;
        }
        $this->createOrUpdatePR($command);
        $this->logIt($command);
    }

    private function isSupported(PutPRToReview $putPRToReview): bool
    {
        $repositoryIdentifier = RepositoryIdentifier::fromString($putPRToReview->repositoryIdentifier);
        $channelIdentifier = ChannelIdentifier::fromString($putPRToReview->channelIdentifier);

        $isSupported = $this->isSupported->repository($repositoryIdentifier)
            && $this->isSupported->channel($channelIdentifier);

        if (!$isSupported) {
            $this->logger->info(
                sprintf(
                    'PR "%s" was not put to review because it is not supported for channel "%s", and repository "%s"',
                    $putPRToReview->PRIdentifier,
                    $putPRToReview->repositoryIdentifier,
                    $putPRToReview->channelIdentifier
                )
            );
        }

        return $isSupported;
    }

    private function createOrUpdatePR(PutPRToReview $command): void
    {
        if ($this->PRExists($command)) {
            $this->resentForReview($command);
        } else {
            $this->createNewPR($command);
        }
    }

    private function PRExists(PutPRToReview $putPRToReview): bool
    {
        try {
            $this->PRRepository->getBy(PRIdentifier::fromString($putPRToReview->PRIdentifier));

            return true;
        } catch (PRNotFoundException $exception) {
            return false;
        }
    }

    private function resentForReview(PutPRToReview $putPRToReview): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString($putPRToReview->PRIdentifier));
        $PR->putToReviewAgainViaMessage(MessageIdentifier::create($putPRToReview->messageIdentifier));
        $this->PRRepository->save($PR);
    }

    private function createNewPR(PutPRToReview $putPRToReview): void
    {
        $this->PRRepository->save(
            PR::create(
                PRIdentifier::create($putPRToReview->PRIdentifier),
                MessageIdentifier::fromString($putPRToReview->messageIdentifier)
            )
        );
    }

    private function logIt(PutPRToReview $command): void
    {
        $this->logger->info(sprintf('PR "%s" has been put to review', $command->PRIdentifier));
    }
}
