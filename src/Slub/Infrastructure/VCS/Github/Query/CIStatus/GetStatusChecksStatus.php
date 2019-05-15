<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\CIStatus;

use GuzzleHttp\Client;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

class GetStatusChecksStatus
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
        $ciStatuses = $this->statuses($PRIdentifier, $commitRef);

        return $this->deductCIStatus($ciStatuses);
    }

    private function statuses(PRIdentifier $PRIdentifier, string $ref): array
    {
        $url = $this->statusesUrl($PRIdentifier, $ref);
        $headers = $this->getHeaders();
        $response = $this->httpClient->get($url, ['headers' => $headers]);

        $content = json_decode($response->getBody()->getContents(), true);
        if (null === $content) {
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

    private function deductCIStatus(array $statuses): string
    {
        $supportedStatuses = array_filter($statuses, function (array $status) {
            return $this->isStatusSupported($status);
        });

        if (empty($supportedStatuses)) {
            return 'PENDING';
        }

        $hasStatus = function (string $statusToFilterOn) {
            return function ($current, $ciStatus) use ($statusToFilterOn) {
                if (null !== $current) {
                    return $current;
                }

                return $statusToFilterOn === $ciStatus['state'];
            };
        };
        $hasCIStatusFailure = array_reduce($supportedStatuses, $hasStatus('failure'));
        $hasCIStatusSuccess = array_reduce($supportedStatuses, $hasStatus('success'));

        if ($hasCIStatusFailure) {
            return 'RED';
        }

        if ($hasCIStatusSuccess) {
            return 'GREEN';
        }

        return 'PENDING';
    }

    private function statusesUrl(PRIdentifier $PRIdentifier, string $ref): string
    {
        $matches = GithubAPIHelper::breakoutPRIdentifier($PRIdentifier);
        $matches[2] = $ref;
        $url = sprintf('https://api.github.com/repos/%s/%s/statuses/%s', ...$matches);

        return $url;
    }

    private function isStatusSupported(array $status): bool
    {
        return in_array($status['context'], $this->supportedCIChecks);
    }

    private function getHeaders(): array
    {
        $headers = GithubAPIHelper::authorizationHeader($this->authToken);
        $headers = array_merge($headers, GithubAPIHelper::acceptPreviewEndpointsHeader());

        return $headers;
    }
}
