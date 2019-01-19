<?php

declare(strict_types=1);

namespace Slub\Domain\Query;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;

interface IsSupportedInterface
{
    public function repository(RepositoryIdentifier $repositoryIdentifier): bool;
}
