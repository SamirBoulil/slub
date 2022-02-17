<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\CIStatus;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClientInterface;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

class GetCheckRunStatus
{
    /** @var string[] */
    private array $supportedCIChecks;

    public function __construct(
        private GithubAPIClientInterface $githubAPIClient,
        string $supportedCIChecks,
        private string $domainName,
        private LoggerInterface $logger
    ) {
        $this->supportedCIChecks = explode(',', $supportedCIChecks);
    }

    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): CheckStatus
    {
        $checkRunsStatus = $this->checkRuns($PRIdentifier, $commitRef);

        return $this->deductCIStatus($checkRunsStatus);
    }

    private function checkRuns(PRIdentifier $PRIdentifier, string $ref): array
    {
        $url = $this->checkRunsUrl($PRIdentifier, $ref);
        $this->logger->critical($url);

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

    private function deductCIStatus(array $allCheckRuns): CheckStatus
    {
        $failedCheckRun = $this->failedCheckRun($allCheckRuns);
        if (null !== $failedCheckRun) {
            return new CheckStatus('RED', $failedCheckRun['details_url'] ?? '');
        }

        if ($this->allCheckRunsSuccess($allCheckRuns)) {
            return new CheckStatus('GREEN');
        }

        $supportedCheckRuns = $this->supportedCheckRuns($allCheckRuns);
        if (empty($supportedCheckRuns)) {
            return new CheckStatus('PENDING');
        }

        if ($this->allCheckRunsSuccess($supportedCheckRuns)) {
            return new CheckStatus('GREEN');
        }

        return new CheckStatus('PENDING');
    }

    private function allCheckRunsSuccess(array $allCheckRuns): bool
    {
        $successfulCICheckRuns = array_filter(
            $allCheckRuns,
            static fn(array $checkRun) => $checkRun['conclusion'] === 'success'
        );

        return \count($successfulCICheckRuns) === \count($allCheckRuns);
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

    private function failedCheckRun(array $allCheckRuns): ?array
    {
        return array_reduce(
            $allCheckRuns,
            static function ($current, $checkRun) {
                if (null !== $current) {
                    return $current;
                }

                return 'failure' === $checkRun['conclusion'] ? $checkRun : $current;
            },
            null
        );
    }

    private function supportedCheckRuns(array $allCheckRuns): array
    {
        return array_filter(
            $allCheckRuns,
            fn(array $checkRun) => \in_array($checkRun['name'], $this->supportedCIChecks, true)
        );
    }
}
