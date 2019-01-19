<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\InMemory\Query;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Webmozart\Assert\Assert;

class InMemoryIsSupported implements IsSupportedInterface
{
    /** @var array */
    private $supportedRepositories;
    /** @var array */
    private $supportedChannels;

    public function __construct(array $supportedRepositories, array $supportedChannels)
    {
        Assert::allString($supportedRepositories);
        Assert::allString($supportedChannels);
        $this->supportedRepositories = $this->createIdentifiers($supportedRepositories);
        $this->supportedChannels = $this->createChannels($supportedChannels);
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

    public function channel($channelIdentifierToCheck): bool
    {
        $channelsFound = array_filter(
            $this->supportedChannels,
            function (ChannelIdentifier $supportedChannel) use ($channelIdentifierToCheck) {
                return $supportedChannel->equals($channelIdentifierToCheck);
            }
        );

        return \count($channelsFound) === 1;
    }

    /**
     * @return RepositoryIdentifier[]
     */
    private function createIdentifiers(array $normalizedIdentifiers): array
    {
        return array_map(
            function (string $normalizedIdentifier) {
                return RepositoryIdentifier::fromString($normalizedIdentifier);
            },
            $normalizedIdentifiers
        );
    }

    /**
     * return ChannelIdentifier[]
     */
    private function createChannels(array $normalizedChannels): array
    {
        return array_map(
            function (string $normalizedIdentifier) {
                return ChannelIdentifier::fromString($normalizedIdentifier);
            },
            $normalizedChannels
        );
    }
}
