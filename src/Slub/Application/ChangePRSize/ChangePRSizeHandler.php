<?php

declare(strict_types=1);

namespace Slub\Application\ChangePRSize;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\IsPRInReview;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Pierrick Martos <pierrick.martos@gmail.com>
 */
class ChangePRSizeHandler
{
    public function __construct(
        private PRRepositoryInterface $PRRepository,
        private IsLarge $isLarge,
        private IsPRInReview $IsPRInReview,
        private LoggerInterface $logger
    ) {
    }

    public function handle(ChangePRSize $changePRSize): void
    {
        if ($this->PRNotInReview($changePRSize)) {
            return;
        }
        $this->analyzePRSize($changePRSize);
    }

    private function PRNotInReview(ChangePRSize $changePRSize): bool
    {
        return !$this->IsPRInReview->fetch(PRIdentifier::fromString($changePRSize->PRIdentifier));
    }

    private function savePRTooLarge(bool $isTooLarge): void
    {
        $this->logIt($isTooLarge);
    }

    private function analyzePRSize(ChangePRSize $changePRSize): void
    {
        $PR = $this->PR($changePRSize);
        $isTooLarge = $this->isTooLarge($changePRSize);
        if ($isTooLarge) {
            $PR->hasBecomeToolarge();
        } else {
            $PR->hasBecomeSmall();
        }
        $this->PRRepository->save($PR);
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
            // $this->logger->info(sprintf('Author has been notified PR "%s" is too large', $changePRSize->PRIdentifier));
        }
    }

}
