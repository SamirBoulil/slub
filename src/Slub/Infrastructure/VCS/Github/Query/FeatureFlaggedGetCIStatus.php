<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CIStatus;
use Slub\Infrastructure\VCS\Github\Query\GraphQL\GetCIStatusViaGraphQL;

/**
 * Routes the CI status computation to the single-query GraphQL implementation when
 * FF_GITHUB_GRAPHQL is on, and to the legacy REST implementation otherwise.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class FeatureFlaggedGetCIStatus implements GetCIStatusInterface
{
    public function __construct(
        private GetCIStatus $legacyGetCIStatus,
        private GetCIStatusViaGraphQL $getCIStatusViaGraphQL,
        private bool $useGraphQL,
    ) {
    }

    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): CIStatus
    {
        if ($this->useGraphQL) {
            return $this->getCIStatusViaGraphQL->fetch($PRIdentifier, $commitRef);
        }

        return $this->legacyGetCIStatus->fetch($PRIdentifier, $commitRef);
    }
}
