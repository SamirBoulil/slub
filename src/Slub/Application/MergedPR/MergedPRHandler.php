<?php

declare(strict_types=1);

namespace Slub\Application\MergedPR;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class MergedPRHandler
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

    public function handle(MergedPR $mergedPR): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString($mergedPR->PRIdentifier));
        $PR->merged();
        $this->PRRepository->save($PR);
    }
}
