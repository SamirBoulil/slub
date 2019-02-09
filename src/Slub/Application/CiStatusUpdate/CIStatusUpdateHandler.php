<?php

declare(strict_types=1);

namespace Slub\Application\CIStatusUpdate;

use Slub\Domain\Entity\PR\PRIdentifier;
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
    private $isRepositorySupported;

    public function __construct(PRRepositoryInterface $PRRepository, IsSupportedInterface $isRepositorySupported)
    {
        $this->PRRepository = $PRRepository;
        $this->isRepositorySupported = $isRepositorySupported;
    }

    public function handle(CIStatusUpdate $CIStatusUpdate): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString($CIStatusUpdate->PRIdentifier));
        if ($CIStatusUpdate->isGreen) {
            $PR->CIIsGreen();
        }
        if (!$CIStatusUpdate->isGreen) {
            $PR->CIIsRed();
        }
        $this->PRRepository->save($PR);
    }
}
