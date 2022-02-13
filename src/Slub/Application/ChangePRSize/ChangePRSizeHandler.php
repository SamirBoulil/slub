<?php

declare(strict_types=1);

namespace Slub\Application\ChangePRSize;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Pierrick Martos <pierrick.martos@gmail.com>
 */
class ChangePRSizeHandler
{
    public function __construct(private PRRepositoryInterface $PRRepository, private IsLarge $isLarge, private LoggerInterface $logger)
    {
    }

    public function handle(ChangePRSize $changePRSize): void
    {
        $isTooLarge = $this->analyzePRSize($changePRSize);
        $this->logIt($isTooLarge, $changePRSize);
    }

    private function analyzePRSize(ChangePRSize $changePRSize): bool
    {
        $PR = $this->PR($changePRSize);
        $isTooLarge = $this->isTooLarge($changePRSize);
        if ($isTooLarge) {
            $this->logger->error('PR TOO LARGE');
            $PR->hasBecomeToolarge();
        } else {
            $this->logger->error('it\'s okay, not too large');
            $PR->hasBecomeSmall();
        }
        $this->PRRepository->save($PR);

        return $isTooLarge;
    }

    private function PR(ChangePRSize $changePRSize): PR
    {
        return $this->PRRepository->getBy(PRIdentifier::fromString($changePRSize->PRIdentifier));
    }

    private function isTooLarge(ChangePRSize $changePRSize): bool
    {
        return $this->isLarge->execute($changePRSize->additions, $changePRSize->deletions);
    }

    private function logIt(bool $isTooLarge, ChangePRSize $changePRSize): void
    {
        if ($isTooLarge) {
            $this->logger->info(sprintf('Author has been notified PR "%s" is too large', $changePRSize->PRIdentifier));
        }
    }
}
