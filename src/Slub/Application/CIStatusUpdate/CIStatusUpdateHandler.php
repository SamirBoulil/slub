<?php

declare(strict_types=1);

namespace Slub\Application\CIStatusUpdate;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class CIStatusUpdateHandler
{
    /** @var PRRepositoryInterface */
    private $PRRepository;

    /** @var IsSupportedInterface */
    private $isSupported;

    public function __construct(PRRepositoryInterface $PRRepository, IsSupportedInterface $isSupported)
    {
        $this->PRRepository = $PRRepository;
        $this->isSupported = $isSupported;
    }

    public function handle(CIStatusUpdate $CIStatusUpdate): void
    {
        if ($this->isUnsupported($CIStatusUpdate)) {
            return;
        }
        $this->updateCIStatus($CIStatusUpdate);
    }

    private function isUnsupported(CIStatusUpdate $CIStatusUpdate): bool
    {
        $repositoryIdentifier = RepositoryIdentifier::fromString($CIStatusUpdate->repositoryIdentifier);

        return $this->isSupported->repository($repositoryIdentifier) === false;
    }

    private function updateCIStatus(CIStatusUpdate $CIStatusUpdate): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString($CIStatusUpdate->PRIdentifier));
        if ($CIStatusUpdate->isGreen) {
            $PR->green();
        }
        if (!$CIStatusUpdate->isGreen) {
            $PR->red();
        }
        $this->PRRepository->save($PR);
    }
}
