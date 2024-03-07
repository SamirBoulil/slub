<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\CIStatus;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClientInterface;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

class GetCheckRunStatus
{
    public function __construct(
        private GithubAPIClientInterface $githubAPIClient,
        private string $domainName,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @return array<CIStatus>
     */
    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): array
    {
        $checkRunsStatus = $this->checkRuns($PRIdentifier, $commitRef);

        return $this->intoCheckStatuses($checkRunsStatus);
    }

    private function checkRuns(PRIdentifier $PRIdentifier, string $ref): array
    {
        $url = $this->checkRunsUrl($PRIdentifier, $ref);
        // $this->logger->critical($url);

        $response = $this->githubAPIClient->get(
            $url,
            ['headers' => GithubAPIHelper::acceptPreviewEndpointsHeader()],
            GithubAPIHelper::repositoryIdentifierFrom($PRIdentifier)
        );

        $content = json_decode($response->getBody()->getContents(), true);
        if (200 !== $response->getStatusCode() || null === $content) {
            throw new \RuntimeException(
                sprintf(
                    'There was a problem when fetching the check runs for PR "%s" at %s',
                    $PRIdentifier->stringValue(),
                    $url
                )
            );
        }

        return $content['check_runs'];
    }

    /**
     * @return array<CIStatus>
     */
    private function intoCheckStatuses(array $checkRuns): array
    {
        return array_map(
            static function ($checkRun) {
                return match ($checkRun['conclusion']) {
                    'success' => CIStatus::green($checkRun['name']),
                    'failure' => CIStatus::red($checkRun['name'], $checkRun['details_url'] ?? ''),
                    default =>  CIStatus::pending($checkRun['name']),
                };
            },
            $checkRuns
        );
    }

    private function checkRunsUrl(PRIdentifier $PRIdentifier, string $ref): string
    {
        return sprintf(
            '%s/repos/%s/commits/%s/check-runs',
            $this->domainName,
            GithubAPIHelper::repositoryIdentifierFrom($PRIdentifier),
            $ref,
        );
    }
}
