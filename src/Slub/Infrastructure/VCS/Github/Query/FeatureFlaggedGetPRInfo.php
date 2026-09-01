<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;
use Slub\Infrastructure\VCS\Github\Query\GraphQL\GetPRInfoViaGraphQL;

/**
 * Routes the PR info fetching to the single-query GraphQL implementation when
 * FF_GITHUB_GRAPHQL is on, and to the legacy REST implementation otherwise.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class FeatureFlaggedGetPRInfo implements GetPRInfoInterface
{
    public function __construct(
        private GetPRInfoInterface $legacyGetPRInfo,
        private GetPRInfoViaGraphQL $getPRInfoViaGraphQL,
        private bool $useGraphQL,
    ) {
    }

    public function fetch(PRIdentifier $PRIdentifier): PRInfo
    {
        if ($this->useGraphQL) {
            return $this->getPRInfoViaGraphQL->fetch($PRIdentifier);
        }

        return $this->legacyGetPRInfo->fetch($PRIdentifier);
    }
}
