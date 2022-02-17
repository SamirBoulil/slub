<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\CIStatus;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClientInterface;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

class GetStatusChecksStatus
{
    public function __construct(
        private GithubAPIClientInterface $githubAPIClient,
        private LoggerInterface $logger,
        private string $domainName
    ) {
    }

    /**
     * @return array<CheckStatus>
     */
    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): array
    {
        $ciStatuses = $this->statuses($PRIdentifier, $commitRef);
        $uniqueCIStatus = $this->sortAndUniqueStatuses($ciStatuses);

        return $this->intoCheckStatuses($uniqueCIStatus);
    }

    private function statuses(PRIdentifier $PRIdentifier, string $ref): array
    {
        $url = $this->statusesUrl($PRIdentifier, $ref);
        $repositoryIdentifier = GithubAPIHelper::repositoryIdentifierFrom($PRIdentifier);
        $response = $this->githubAPIClient->get(
            $url,
            ['headers' => GithubAPIHelper::acceptPreviewEndpointsHeader()],
            $repositoryIdentifier
        );

        $content = json_decode($response->getBody()->getContents(), true);

        if (200 !== $response->getStatusCode() || null === $content) {
            throw new \RuntimeException(
                sprintf(
                    'There was a problem when fetching the statuses for PR "%s" at %s',
                    $PRIdentifier->stringValue(),
                    $url
                )
            );
        }

        return $content;
    }

    private function statusesUrl(PRIdentifier $PRIdentifier, string $ref): string
    {
        return sprintf(
            '%s/repos/%s/statuses/%s',
            $this->domainName,
            GithubAPIHelper::repositoryIdentifierFrom($PRIdentifier),
            $ref
        );
    }

    private function sortAndUniqueStatuses(array $ciStatuses): array
    {
        $ciStatuses = $this->sortStatusesByUpdatedAt($ciStatuses);

        return array_reduce($ciStatuses, function (array $statuses, $status) {
            $statuses[$status['context']] = $status;

            return $statuses;
        },                  []);
    }

    private function sortStatusesByUpdatedAt(array $ciStatuses): array
    {
        usort(
            $ciStatuses,
            static function ($a, $b) {
                $ad = new \DateTime($a['updated_at']);
                $bd = new \DateTime($b['updated_at']);

                return $ad <=> $bd;
            }
        );

        return $ciStatuses;
    }

    /**
     * @return array<CheckStatus>
     */
    private function intoCheckStatuses(array $checkRuns): array
    {
        return array_values(array_map(
            static function ($checkRun) {
                return match ($checkRun['state']) {
                    'success' => CheckStatus::green($checkRun['context']),
                    'failure' => CheckStatus::red($checkRun['context'], $checkRun['target_url']),
                    default => CheckStatus::pending($checkRun['context']),
                };
            },
            $checkRuns
        ));
    }
}
