<?php

declare(strict_types=1);

namespace Slub\Application\CIStatusUpdate;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;
use Webmozart\Assert\Assert;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class CIStatusUpdateHandler
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

    public function handle(CIStatusUpdate $CIStatusUpdate): void
    {
        if (!$this->isSupported($CIStatusUpdate)) {
            return;
        }
        $this->updateCIStatus($CIStatusUpdate);
        $this->logIt($CIStatusUpdate);
    }

    private function isSupported(CIStatusUpdate $CIStatusUpdate): bool
    {
        $repositoryIdentifier = RepositoryIdentifier::fromString($CIStatusUpdate->repositoryIdentifier);
        Assert::string($CIStatusUpdate->status);

        return $this->isSupported->repository($repositoryIdentifier);
    }

    private function updateCIStatus(CIStatusUpdate $CIStatusUpdate): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString($CIStatusUpdate->PRIdentifier));
        switch ($CIStatusUpdate->status) {
            case 'GREEN': $PR->green(); break;
            case 'RED': $PR->red(); break;
            case 'PENDING': $PR->pending(); break;
        }
        $this->PRRepository->save($PR);
    }

    private function logIt(CIStatusUpdate $CIStatusUpdate): void
    {
        if ($CIStatusUpdate->isGreen) {
            $logMessage = sprintf('Squad has been notified PR "%s" has a Green CI', $CIStatusUpdate->PRIdentifier);
        } else {
            $logMessage = sprintf('Squad has been notified PR "%s" has a Red CI', $CIStatusUpdate->PRIdentifier);
        }
        $this->logger->info($logMessage);
    }
}
