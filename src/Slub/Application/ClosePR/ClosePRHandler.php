<?php

declare(strict_types=1);

namespace Slub\Application\ClosePR;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ClosePRHandler
{
    public function __construct(private PRRepositoryInterface $PRRepository, private LoggerInterface $logger)
    {
    }

    public function handle(ClosePR $command): void
    {
        $this->closePR($command);
        $this->logIt($command);
    }

    private function closePR(ClosePR $closePR): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString($closePR->PRIdentifier));
        $PR->close($closePR->isMerged);
        $this->PRRepository->save($PR);
    }

    private function logIt(ClosePR $command): void
    {
        $logMessage = sprintf('Squad has been notified PR "%s" is %s merged and closed', $command->PRIdentifier, $command->isMerged ? '' : 'not');
        $this->logger->info($logMessage);
    }
}
