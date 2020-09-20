<?php

declare(strict_types=1);

namespace Slub\Domain\Query;

use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;

interface IsSupportedInterface
{
    public function repository(RepositoryIdentifier $repositoryIdentifier): bool;

    public function workspace(WorkspaceIdentifier $workspaceIdentifier): bool;
}
