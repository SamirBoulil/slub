<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\CIStatus;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

class GetCheckRunStatus
{
    /** @var string[] */
    private array $supportedCIChecks;

    public function __construct(
        private GithubAPIClient $githubAPIClient,
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
            throw new \RuntimeException(sprintf('There was a problem when fetching the check runs for PR "%s" at %s', $PRIdentifier->stringValue(), $url));
        }

        return $content['check_runs'];
    }

    private function deductCIStatus(array $checkRuns): CheckStatus
    {
        $getCheckRuns = fn (string $statusToFilterOn) => function ($current, $checkRun) use ($statusToFilterOn) {
            if (null !== $current) {
                return $current;
            }

            return $statusToFilterOn === $checkRun['conclusion'] ? $checkRun : $current;
        };

        $CICheckAFailure = array_reduce($checkRuns, $getCheckRuns('failure'), null);
        if (null !== $CICheckAFailure) {
            return new CheckStatus('RED', $CICheckAFailure['details_url'] ?? '');
        }

        $supportedCheckRuns = array_filter(
            $checkRuns,
            fn (array $checkRun) => $this->isCheckRunSupported($checkRun)
        );

        if (empty($supportedCheckRuns)) {
            return new CheckStatus('PENDING');
        }

        $CICheckSuccess = array_reduce($supportedCheckRuns, $getCheckRuns('success'), null);
        if (null !== $CICheckSuccess) {
            return new CheckStatus('GREEN');
        }

        return new CheckStatus('PENDING');
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

    private function isCheckRunSupported(array $checkRun): bool
    {
        return in_array($checkRun['name'], $this->supportedCIChecks);
    }
}
