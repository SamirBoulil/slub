<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\CIStatus;

use GuzzleHttp\Client;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

/**
 * This query is not used as long as Github will not make status checks part of the check suite result.
 *
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetCheckSuiteStatus
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

    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): CheckStatus
    {
        $checkSuite = $this->checkSuite($PRIdentifier, $commitRef);
        if ($this->isCheckSuiteStatus($checkSuite, 'failure')) {
            return new CheckStatus('RED', $this->buildLink($checkSuite));
        }
        if ($this->isCheckSuiteStatus($checkSuite, 'success')) {
            return new CheckStatus('GREEN', '');
        }

        return new CheckStatus('PENDING', '');
    }

    private function isCheckSuiteStatus(array $checkSuites, string $expectedConclusion): bool
    {
        $checkSuite = $checkSuites['check_suites'][0];

        return 'completed' === $checkSuite['status'] && $expectedConclusion === $checkSuite['conclusion'];
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

    private function getHeaders(): array
    {
        $headers = GithubAPIHelper::authorizationHeader($this->authToken);
        $headers = array_merge($headers, GithubAPIHelper::acceptPreviewEndpointsHeader());
        return $headers;
    }

    private function buildLink(array $checkSuites): string
    {
        $checkSuite = $checkSuites['check_suites'][0];

        return $checkSuite['details_url'] ?? '';
    }
}
