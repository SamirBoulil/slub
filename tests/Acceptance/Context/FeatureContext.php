<?php

declare(strict_types=1);

namespace Tests\Acceptance\Context;

use Behat\Behat\Context\Context;
use Slub\Domain\Repository\PRRepositoryInterface;
use Slub\Infrastructure\Persistence\FileBased\Repository\SqlPRRepository;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
abstract class FeatureContext implements Context
{
    /** @var PRRepositoryInterface */
    protected $PRRepository;

    public function __construct(SqlPRRepository $repository)
    {
        $this->PRRepository = $repository;
        $this->PRRepository->resetFile();
    }
}
