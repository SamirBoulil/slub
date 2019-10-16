<?php

declare(strict_types=1);

namespace Slub\Domain\Repository;

use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;

interface PRRepositoryInterface
{
    public function save(PR $PR): void;

    /**
     * @throws PRNotFoundException
     */
    public function getBy(PRIdentifier $PRidentifier): PR;

    public function reset();

    /**
     * @return PR[]
     */
    public function findPRToReviewNotGTMed(): array;

    /**
     * @return PR[]
     */
    public function all(): array;

    public function unpublishPR(PRIdentifier $PRIdentifierToDelete): void;
}
