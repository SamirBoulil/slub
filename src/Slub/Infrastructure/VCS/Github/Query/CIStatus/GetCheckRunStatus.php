<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\CIStatus;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

class GetCheckRunStatus
{
    /** @var Client */
    private $httpClient;

    /** @var string */
    private $authToken;

    /** @var string[] */
    private $supportedCIChecks;

    /** @var string */
    private $domainName;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        Client $httpClient,
        string $authToken,
        string $supportedCIChecks,
        string $domainName,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->authToken = $authToken;
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

        $headers = $this->getHeaders();
        $response = $this->httpClient->get($url, ['headers' => $headers]);

        $content = json_decode($response->getBody()->getContents(), true);
        if (null === $content) {
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

    private function deductCIStatus(array $checkRuns): CheckStatus
    {
        $getCheckRuns = function (string $statusToFilterOn) {
            return function ($current, $checkRun) use ($statusToFilterOn) {
                if (null !== $current) {
                    return $current;
                }

                return $statusToFilterOn === $checkRun['conclusion'] ? $checkRun : $current;
            };
        };

        $CICheckAFailure = array_reduce($checkRuns, $getCheckRuns('failure'), null);
        if (null !== $CICheckAFailure) {
            return new CheckStatus('RED', $CICheckAFailure['details_url'] ?? '');
        }

        $supportedCheckRuns = array_filter(
            $checkRuns,
            function (array $checkRun) {
                return $this->isCheckRunSupported($checkRun);
            }
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
        $url = sprintf(
            '%s/repos/%s/%s/commits/%s/check-runs',
            $this->domainName,
            ...$matches
        );

        return $url;
    }

    private function getHeaders(): array
    {
        $headers = GithubAPIHelper::authorizationHeader($this->authToken);
        $headers = array_merge($headers, GithubAPIHelper::acceptPreviewEndpointsHeader());

        return $headers;
    }

    private function isCheckRunSupported(array $checkRun): bool
    {
        return in_array($checkRun['name'], $this->supportedCIChecks);
    }
}
