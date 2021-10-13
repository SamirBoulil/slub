<?php

declare(strict_types=1);

namespace Slub\Application\WarnLargePR;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;
use Webmozart\Assert\Assert;

/**
 * @author    Pierrick Martos <pierrick.martos@gmail.com>
 */
class WarnLargePRHandler
{
    private PRRepositoryInterface $PRRepository;

    private IsSupportedInterface $isSupported;

    private LoggerInterface $logger;

    private int $prSizeLimit;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        IsSupportedInterface $isSupported,
        LoggerInterface $logger,
        int $prSizeLimit = 500
    ) {
        $this->PRRepository = $PRRepository;
        $this->isSupported = $isSupported;
        $this->logger = $logger;
        $this->prSizeLimit = $prSizeLimit;
    }

    public function handle(WarnLargePR $WarnLargePR): void
    {
        if (!$this->isSupported($WarnLargePR)) {
            return;
        }
        $this->warnLargePR($WarnLargePR);
        $this->logIt($WarnLargePR);
    }

    private function isSupported(WarnLargePR $WarnLargePR): bool
    {
        $repositoryIdentifier = RepositoryIdentifier::fromString($WarnLargePR->repositoryIdentifier);

        return $this->isSupported->repository($repositoryIdentifier);
    }

    private function warnLargePR(WarnLargePR $WarnLargePR): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString($WarnLargePR->PRIdentifier));
        if ($WarnLargePR->additions > $this->prSizeLimit || $WarnLargePR->deletions > $this->prSizeLimit) {
            $PR->large();
        } else if ($WarnLargePR->additions <= $this->prSizeLimit && $WarnLargePR <= $this->prSizeLimit) {
            $PR->small();
        }

        $this->PRRepository->save($PR);
    }

    private function logIt(WarnLargePR $WarnLargePR): void
    {
        if ($WarnLargePR->additions > $this->prSizeLimit || $WarnLargePR->deletions > $this->prSizeLimit) {
            $logMessage = sprintf('Author has been notified PR "%s" is too large', $WarnLargePR->PRIdentifier);
            $this->logger->info($logMessage);
        }
    }
}
