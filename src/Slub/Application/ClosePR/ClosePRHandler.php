<?php

declare(strict_types=1);

namespace Slub\Application\ClosePR;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ClosePRHandler
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

    public function handle(ClosePR $command): void
    {
        if (!$this->isSupported($command)) {
            return;
        }
        $this->closePR($command);
        $this->logIt($command);
    }

    private function isSupported(ClosePR $closePR): bool
    {
        $repositoryIdentifier = RepositoryIdentifier::fromString($closePR->repositoryIdentifier);

        return $this->isSupported->repository($repositoryIdentifier);
    }

    private function closePR(ClosePR $closePR): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString($closePR->PRIdentifier));
        $PR->close($closePR->isMerged);
        $this->PRRepository->save($PR);
    }

    private function logIt(ClosePR $command): void
    {
        $logMessage = sprintf('Squad has been notified PR "%s" is closed', $command->PRIdentifier);
        $this->logger->info($logMessage);
    }
}
