<?php

declare(strict_types=1);

namespace Slub\Application\MergedPR;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class MergedPRHandler
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

    public function handle(MergedPR $command): void
    {
        if (!$this->isSupported($command)) {
            return;
        }
        $this->setPRMerged($command);
        $this->logIt($command);
    }

    private function isSupported(MergedPR $mergedPR): bool
    {
        $repositoryIdentifier = RepositoryIdentifier::fromString($mergedPR->repositoryIdentifier);

        return $this->isSupported->repository($repositoryIdentifier);
    }

    private function setPRMerged(MergedPR $mergedPR): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString($mergedPR->PRIdentifier));
        $PR->merged();
        $this->PRRepository->save($PR);
    }

    private function logIt(MergedPR $command): void
    {
        $logMessage = sprintf('Squad has been notified PR "%s" is merged', $command->PRIdentifier);
        $this->logger->info($logMessage);
    }
}
