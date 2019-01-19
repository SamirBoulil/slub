<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\InMemory\Query;

use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Webmozart\Assert\Assert;

class InMemoryIsSupported implements IsSupportedInterface
{
    /** @var array */
    private $supportedRepositories;

    public function __construct(array $supportedRepositories)
    {
        Assert::allString($supportedRepositories);
        $this->supportedRepositories = $this->createIdentifiers($supportedRepositories);
    }

    public function repository(RepositoryIdentifier $repositoryIdentifierToCheck): bool
    {
        $repositoriesFound = array_filter(
            $this->supportedRepositories,
            function (RepositoryIdentifier $supportedRepository) use ($repositoryIdentifierToCheck) {
                return $supportedRepository->equals($repositoryIdentifierToCheck);
            }
        );

        return \count($repositoriesFound) === 1;
    }

    /**
     * @return RepositoryIdentifier[]
     */
    private function createIdentifiers(array $normalizedIdentifiers): array
    {
        return array_map(
            function (string $normalizedIdentifiers) {
                return RepositoryIdentifier::fromString($normalizedIdentifiers);
            },
            $normalizedIdentifiers
        );
    }
}
