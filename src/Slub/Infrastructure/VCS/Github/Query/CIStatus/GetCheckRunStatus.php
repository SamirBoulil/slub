<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\CIStatus;

use GuzzleHttp\Client;
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

    public function __construct(Client $httpClient, string $authToken, string $supportedCIChecks)
    {
        $this->httpClient = $httpClient;
        $this->authToken = $authToken;
        $this->supportedCIChecks = explode(',', $supportedCIChecks);
    }

    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): string
    {
        $checkRunsStatus = $this->checkRuns($PRIdentifier, $commitRef);

        return $this->deductCIStatus($checkRunsStatus);
    }

    private function checkRuns(PRIdentifier $PRIdentifier, string $ref): array
    {
        $url = $this->checkRunsUrl($PRIdentifier, $ref);
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

        return $content;
    }

    private function deductCIStatus(array $checkRuns): string
    {
        $supportedCheckRuns = array_filter(
            $checkRuns['check_runs'],
            function (array $checkRun) {
                return $this->isCheckRunSupported($checkRun);
            }
        );

        if (empty($supportedCheckRuns)) {
            return 'PENDING';
        }

        $hasCheckRun = function (string $statusToFilterOn) {
            return function ($current, $checkRun) use ($statusToFilterOn) {
                if (null !== $current) {
                    return $current;
                }

                return $statusToFilterOn === $checkRun['conclusion'];
            };
        };

        $hasCICheckAFailure = array_reduce($supportedCheckRuns, $hasCheckRun('failure'));
        $hasCICheckSuccess = array_reduce($supportedCheckRuns, $hasCheckRun('success'));

        if ($hasCICheckAFailure) {
            return 'RED';
        }

        if ($hasCICheckSuccess) {
            return 'GREEN';
        }

        return 'PENDING';
    }

    private function checkRunsUrl(PRIdentifier $PRIdentifier, string $ref): string
    {
        $matches = GithubAPIHelper::breakoutPRIdentifier($PRIdentifier);
        $matches[2] = $ref;
        $url = sprintf('https://api.github.com/repos/%s/%s/commits/%s/check-runs', ...$matches);

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
