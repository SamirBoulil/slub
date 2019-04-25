<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use GuzzleHttp\Client;
use Slub\Domain\Entity\PR\PRIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class GetCIStatus
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
        $checkSuite = $this->checkSuite($PRIdentifier, $commitRef);
        if ($this->isCheckSuiteStatus($checkSuite, 'failure')) {
            return 'RED';
        }
        if ($this->isCheckSuiteStatus($checkSuite, 'success')) {
            return 'GREEN';
        }
        $checkRuns = $this->checkRuns($PRIdentifier, $commitRef);

        return $this->deductCIStatus($checkRuns);
    }

    private function checkSuite(PRIdentifier $PRIdentifier, string $commitRef)
    {
        $url = $this->getCheckSuiteUrl($PRIdentifier, $commitRef);
        $headers = $this->getHeaders();
        $response = $this->httpClient->get($url, ['headers' => $headers]);

        $content = json_decode($response->getBody()->getContents(), true);
        if (null === $content) {
            throw new \RuntimeException(
                sprintf(
                    'There was a problem when fetching the reviews for PR "%s" at %s',
                    $PRIdentifier->stringValue(),
                    $url
                )
            );
        }

        return $content;
    }

    private function getCheckSuiteUrl(PRIdentifier $PRIdentifier, string $commitRef): string
    {
        $matches = GithubAPIHelper::breakoutPRIdentifier($PRIdentifier);
        $matches[2] = $commitRef;
        $url = sprintf('https://api.github.com/repos/%s/%s/commits/%s/check-suites', ...$matches);

        return $url;
    }

    private function checkRuns(PRIdentifier $PRIdentifier, string $ref): array
    {
        $url = $this->getCheckRunsUrl($PRIdentifier, $ref);
        $headers = $this->getHeaders();
        $response = $this->httpClient->get($url, ['headers' => $headers]);

        $content = json_decode($response->getBody()->getContents(), true);
        if (null === $content) {
            throw new \RuntimeException(
                sprintf(
                    'There was a problem when fetching the reviews for PR "%s" at %s',
                    $PRIdentifier->stringValue(),
                    $url
                )
            );
        }

        return $content;
    }

    private function getCheckRunsUrl(PRIdentifier $PRIdentifier, $ref): string
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

    private function deductCIStatus(array $checkRuns): string
    {
        return array_reduce(
            $checkRuns['check_runs'],
            function (string $current, array $ciStatus) {
                if (!in_array($ciStatus['name'], $this->supportedCIChecks)
                    || 'completed' !== $ciStatus['status']
                    || $current === 'RED'
                ) {
                    return $current;
                }

                if ('success' === $ciStatus['conclusion']) {
                    return 'GREEN';
                }

                if ('failure' === $ciStatus['conclusion']) {
                    return 'RED';
                }

                return $current;
            },
            'PENDING'
        );
    }

    private function isCheckSuiteStatus(array $checkSuites, string $expectedConclusion): bool
    {
        $checkSuite = $checkSuites['check_suites'][0];

        return 'completed' === $checkSuite['status'] && $expectedConclusion === $checkSuite['conclusion'];
    }
}
