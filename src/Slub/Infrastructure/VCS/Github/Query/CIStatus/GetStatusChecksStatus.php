<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\CIStatus;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClientInterface;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

class GetStatusChecksStatus
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
        $ciStatuses = $this->statuses($PRIdentifier, $commitRef);
        $uniqueCiStatus = $this->sortAndUniqueStatuses($ciStatuses);

        return $this->deductCIStatus($uniqueCiStatus);
    }

    private function statuses(PRIdentifier $PRIdentifier, string $ref): array
    {
        $url = $this->statusesUrl($PRIdentifier, $ref);
        $repositoryIdentifier = GithubAPIHelper::repositoryIdentifierFrom($PRIdentifier);
        $response = $this->githubAPIClient->get(
            $url,
            ['headers' => GithubAPIHelper::acceptPreviewEndpointsHeader()],
            $repositoryIdentifier
        );

        $content = json_decode($response->getBody()->getContents(), true);

        if (200 !== $response->getStatusCode() || null === $content) {
            throw new \RuntimeException(sprintf('There was a problem when fetching the statuses for PR "%s" at %s', $PRIdentifier->stringValue(), $url));
        }

        return $content;
    }

    private function deductCIStatus(array $allStatuses): CheckStatus
    {
        $failedStatus = $this->failedStatus($allStatuses);
        if (null !== $failedStatus) {
            return new CheckStatus('RED', $failedStatus['target_url'] ?? '');
        }

        if ($this->allStatusesSuccess($allStatuses)) {
            return new CheckStatus('GREEN');
        }

        $supportedStatuses = $this->supportedStatuses($allStatuses);
        if (empty($supportedStatuses)) {
            return new CheckStatus('PENDING');
        }

        if ($this->allStatusesSuccess($supportedStatuses)) {
            return new CheckStatus('GREEN');
        }

        return new CheckStatus('PENDING');
    }

    private function failedStatus(array $allStatuses): ?array
    {
        return array_reduce(
            $allStatuses,
            static function ($current, $ciStatus) {
                if (null !== $current) {
                    return $current;
                }

                return ('failure' === $ciStatus['state']) ? $ciStatus : $current;
            },
            null
        );
    }

    private function allStatusesSuccess(array $allStatuses): bool
    {
        $successfulStatuses = array_filter(
            $allStatuses,
            static fn(array $status) => 'success' === $status['state']
        );

        return \count($successfulStatuses) === \count($allStatuses);
    }

    private function supportedStatuses(array $allStatuses): array
    {
        return array_filter(
            $allStatuses,
            fn(array $status) => \in_array($status['context'], $this->supportedCIChecks, true)
        );
    }

    private function statusesUrl(PRIdentifier $PRIdentifier, string $ref): string
    {
        return sprintf(
            '%s/repos/%s/statuses/%s',
            $this->domainName,
            GithubAPIHelper::repositoryIdentifierFrom($PRIdentifier),
            $ref
        );
    }

    private function sortAndUniqueStatuses(array $ciStatuses): array
    {
        $ciStatuses = $this->sortStatusesByUpdatedAt($ciStatuses);

        return array_reduce($ciStatuses, function (array $statuses, $status) {
            $statuses[$status['context']] = $status;

            return $statuses;
        }, []);
    }

    private function sortStatusesByUpdatedAt(array $ciStatuses): array
    {
        usort(
            $ciStatuses,
            function ($a, $b) {
                $ad = new \DateTime($a['updated_at']);
                $bd = new \DateTime($b['updated_at']);
                return $ad <=> $bd;
            }
        );

        return $ciStatuses;
    }
}
