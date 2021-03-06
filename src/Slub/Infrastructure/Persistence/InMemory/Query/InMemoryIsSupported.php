<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\InMemory\Query;

use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Webmozart\Assert\Assert;

class InMemoryIsSupported implements IsSupportedInterface
{
    private array $supportedRepositories;

    private array $supportedWorkspace;

    public function __construct(string $commaSeparatedRepositories, string $commaSeparatedchannels)
    {
        $supportedRepositories = explode(',', $commaSeparatedRepositories);
        $supportedWorkspacess = explode(',', $commaSeparatedchannels);
        Assert::allString($supportedRepositories);
        Assert::allString($supportedWorkspacess);
        $this->supportedRepositories = $this->createIdentifiers($supportedRepositories);
        $this->supportedWorkspace = $this->createWorkspaces($supportedWorkspacess);
    }

    public function repository(RepositoryIdentifier $repositoryIdentifierToCheck): bool
    {
        $repositoriesFound = array_filter(
            $this->supportedRepositories,
            static fn (RepositoryIdentifier $supportedRepository) => $supportedRepository->equals($repositoryIdentifierToCheck)
        );

        return 1 === \count($repositoriesFound);
    }

    public function workspace(WorkspaceIdentifier $workspaceIdentifierToCheck): bool
    {
        $found = array_filter(
            $this->supportedWorkspace,
            fn (WorkspaceIdentifier $supportedChannel) => $supportedChannel->equals($workspaceIdentifierToCheck)
        );

        return 1 === \count($found);
    }

    /**
     * @return RepositoryIdentifier[]
     */
    private function createIdentifiers(array $normalizedIdentifiers): array
    {
        return array_map(
            fn (string $normalizedIdentifier) => RepositoryIdentifier::fromString($normalizedIdentifier),
            $normalizedIdentifiers
        );
    }

    /**
     * @return WorkspaceIdentifier[]
     */
    private function createWorkspaces(array $normalizedWorkspaceIdentifiers): array
    {
        return array_map(
            fn (string $normalizedIdentifier) => WorkspaceIdentifier::fromString($normalizedIdentifier),
            $normalizedWorkspaceIdentifiers
        );
    }
}
