<?php

declare(strict_types=1);

namespace Slub\Application\CIStatusUpdate;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\BuildLink;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CIStatusUpdateHandler
{
    public function __construct(private PRRepositoryInterface $PRRepository, private LoggerInterface $logger)
    {
    }

    public function handle(CIStatusUpdate $CIStatusUpdate): void
    {
        $this->updateCIStatus($CIStatusUpdate);
        $this->logIt($CIStatusUpdate);
    }

    private function updateCIStatus(CIStatusUpdate $CIStatusUpdate): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString($CIStatusUpdate->PRIdentifier));
        switch ($CIStatusUpdate->status) {
            case 'GREEN': $PR->green(); break;
            case 'RED': $PR->red($this->buildLink($CIStatusUpdate)); break;
            case 'PENDING': $PR->pending(); break;
        }
        $this->PRRepository->save($PR);
    }

    private function logIt(CIStatusUpdate $CIStatusUpdate): void
    {
        $logMessage = '';
        if ('GREEN' === $CIStatusUpdate->status) {
            $logMessage = sprintf('Squad has been notified PR "%s" has a Green CI', $CIStatusUpdate->PRIdentifier);
        }
        if ('RED' === $CIStatusUpdate->status) {
            $logMessage = sprintf('Squad has been notified PR "%s" has a Red CI', $CIStatusUpdate->PRIdentifier);
        }
        if ('PENDING' === $CIStatusUpdate->status) {
            $logMessage = sprintf('Squad has been notified PR "%s" has a pending CI', $CIStatusUpdate->PRIdentifier);
        }
        $this->logger->info($logMessage);
    }

    private function buildLink(CIStatusUpdate $CIStatusUpdate): BuildLink
    {
        $buildLink = $CIStatusUpdate->buildLink;

        return empty($buildLink) ? BuildLink::none() : BuildLink::fromURL($CIStatusUpdate->buildLink ?? '');
    }
}
