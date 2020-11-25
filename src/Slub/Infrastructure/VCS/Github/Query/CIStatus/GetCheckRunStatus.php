<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\CIStatus;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

class GetCheckRunStatus
{
    private GithubAPIClient $githubAPIClient;

    /** @var string[] */
    private array $supportedCIChecks;

    private string $domainName;

    private LoggerInterface $logger;

    public function __construct(
        GithubAPIClient $githubAPIClient,
        string $supportedCIChecks,
        string $domainName,
        LoggerInterface $logger
    ) {
        $this->githubAPIClient = $githubAPIClient;
        $this->supportedCIChecks = explode(',', $supportedCIChecks);
        $this->domainName = $domainName;
        $this->logger = $logger;
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

        $repositoryIdentifier = $this->repositoryIdentifier($PRIdentifier);
        $response = $this->githubAPIClient->get(
            $url,
            ['headers' => GithubAPIHelper::acceptPreviewEndpointsHeader()],
            $repositoryIdentifier
        );

        $content = json_decode($response->getBody()->getContents(), true);
        if (null === $content) {
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
        $matches = GithubAPIHelper::breakoutPRIdentifier($PRIdentifier);
        $matches[2] = $ref;

        return sprintf(
            '%s/repos/%s/%s/commits/%s/check-runs',
            $this->domainName,
            ...$matches
        );
    }

    private function isCheckRunSupported(array $checkRun): bool
    {
        return in_array($checkRun['name'], $this->supportedCIChecks);
    }

    private function repositoryIdentifier(PRIdentifier $PRIdentifier): string
    {
        return sprintf('%s/%s', ...GithubAPIHelper::breakoutPRIdentifier($PRIdentifier));
    }
}
