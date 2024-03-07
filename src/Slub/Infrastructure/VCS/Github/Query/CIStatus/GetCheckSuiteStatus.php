<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\CIStatus;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClientInterface;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

/**
 * This query is not used as long as Github will not make status checks part of the check suite result.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class GetCheckSuiteStatus
{
    /** @var string[] */
    private array $supportedCIChecks;

    public function __construct(
        private GithubAPIClientInterface $githubAPIClient,
        string $supportedCIChecks,
        private string $domainName
    ) {
        $this->supportedCIChecks = explode(',', $supportedCIChecks);
    }

    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): CIStatus
    {
        $checkSuite = $this->checkSuite($PRIdentifier, $commitRef);
        if ($this->isCheckSuiteStatus($checkSuite, 'failure')) {
            return CIStatus::red('RENAME_ME', $this->buildLink($checkSuite));
        }
        if ($this->isCheckSuiteStatus($checkSuite, 'success')) {
            return CIStatus::green('RENAME_ME');
        }

        return CIStatus::pending('RENAME_ME');
    }

    private function isCheckSuiteStatus(array $checkSuites, string $expectedConclusion): bool
    {
        $checkSuite = $checkSuites['check_suites'][0];

        return 'completed' === $checkSuite['status'] && $expectedConclusion === $checkSuite['conclusion'];
    }

    private function checkSuite(PRIdentifier $PRIdentifier, string $commitRef)
    {
        $url = $this->getCheckSuiteUrl($PRIdentifier, $commitRef);
        $response = $this->githubAPIClient->get(
            $url,
            ['headers' => GithubAPIHelper::acceptPreviewEndpointsHeader()],
            GithubAPIHelper::repositoryIdentifierFrom($PRIdentifier)
        );

        $content = json_decode($response->getBody()->getContents(), true);
        if (200 !== $response->getStatusCode() || null === $content) {
            throw new \RuntimeException(sprintf('There was a problem when fetching the reviews for PR "%s" at %s', $PRIdentifier->stringValue(), $url));
        }

        return $content;
    }

    private function getCheckSuiteUrl(PRIdentifier $PRIdentifier, string $commitRef): string
    {
        return sprintf(
            '%s/repos/%s/commits/%s/check-suites',
            $this->domainName,
            GithubAPIHelper::repositoryIdentifierFrom($PRIdentifier),
            $commitRef
        );
    }

    private function buildLink(array $checkSuites): string
    {
        $checkSuite = $checkSuites['check_suites'][0];

        return $checkSuite['details_url'] ?? '';
    }
}
