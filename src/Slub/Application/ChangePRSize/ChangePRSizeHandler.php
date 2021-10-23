<?php

declare(strict_types=1);

namespace Slub\Application\ChangePRSize;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Pierrick Martos <pierrick.martos@gmail.com>
 */
class ChangePRSizeHandler
{
    private PRRepositoryInterface $PRRepository;

    private LoggerInterface $logger;

    private IsLarge $isLarge;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        IsLarge $isLarge,
        LoggerInterface $logger
    ) {
        $this->PRRepository = $PRRepository;
        $this->logger = $logger;
        $this->isLarge = $isLarge;
    }

    public function handle(ChangePRSize $changePRSize): void
    {
        $this->warnLargePR($changePRSize);
        $this->logIt($changePRSize);
    }

    private function warnLargePR(ChangePRSize $changePRSize): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString($changePRSize->PRIdentifier));
        if ($this->isLarge->execute($changePRSize->additions, $changePRSize->deletions)) {
            $PR->hasBecomeToolarge();
        } else {
            $PR->small();
        }

        $this->PRRepository->save($PR);
    }

    private function logIt(ChangePRSize $changePRSize): void
    {
        if ($this->isLarge->execute($changePRSize->additions, $changePRSize->deletions)) {
            $logMessage = sprintf('Author has been notified PR "%s" is too large', $changePRSize->PRIdentifier);
            $this->logger->info($logMessage);
        }
    }
}
