<?php

declare(strict_types=1);

namespace Slub\Application\WarnLargePR;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Pierrick Martos <pierrick.martos@gmail.com>
 */
class WarnLargePRHandler
{
    private PRRepositoryInterface $PRRepository;

    private LoggerInterface $logger;

    private int $prSizeLimit;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        LoggerInterface $logger,
        int $prSizeLimit = 500
    ) {
        $this->PRRepository = $PRRepository;
        $this->logger = $logger;
        $this->prSizeLimit = $prSizeLimit;
    }

    public function handle(WarnLargePR $warnLargePR): void
    {
        $this->warnLargePR($warnLargePR);
        $this->logIt($warnLargePR);
    }

    private function warnLargePR(WarnLargePR $warnLargePR): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString($warnLargePR->PRIdentifier));
        if ($this->isPRTooLarge($warnLargePR)) {
            $PR->large();
        } else {
            $PR->small();
        }

        $this->PRRepository->save($PR);
    }

    private function logIt(WarnLargePR $warnLargePR): void
    {
        if ($this->isPRTooLarge($warnLargePR)) {
            $logMessage = sprintf('Author has been notified PR "%s" is too large', $warnLargePR->PRIdentifier);
            $this->logger->info($logMessage);
        }
    }

    private function isPRTooLarge(WarnLargePR $warnLargePR)
    {
        if ($warnLargePR->additions > $this->prSizeLimit || $warnLargePR->deletions > $this->prSizeLimit) {
            return true;
        }

        if ($warnLargePR->additions <= $this->prSizeLimit && $warnLargePR <= $this->prSizeLimit) {
            return false;
        }

        return false;
    }
}
