<?php

declare(strict_types=1);

namespace Tests\Acceptance\Context;

use Behat\Behat\Context\Context;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
abstract class FeatureContext implements Context
{
    /** @var PRRepositoryInterface */
    protected $PRRepository;

    public function __construct(PRRepositoryInterface $PRRepository)
    {
        $this->PRRepository = $PRRepository;
    }

    /**
     * @BeforeScenario
     */
    public function cleanDB(): void
    {
        $this->PRRepository->reset();
    }
}
