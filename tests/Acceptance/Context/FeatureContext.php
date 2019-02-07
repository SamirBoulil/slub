<?php

declare(strict_types=1);

namespace Tests\Acceptance\Context;

use Behat\Behat\Context\Context;
use Slub\Domain\Repository\PRRepositoryInterface;
use Slub\Infrastructure\Persistence\FileBased\Repository\FileBasedPRRepository;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
abstract class FeatureContext implements Context
{
    /** @var PRRepositoryInterface */
    protected $PRRepository;

    public function __construct(FileBasedPRRepository $repository)
    {
        $this->PRRepository = $repository;
        $this->PRRepository->resetFile();
    }
}
