<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\CIStatus;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
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
        $ciStatuses = $this->statuses($PRIdentifier, $commitRef);
        $this->logger->critical((string) json_encode($ciStatuses));
        $uniqueCiStatus = $this->sortAndUniqueStatuses($ciStatuses);

        return $this->deductCIStatus($uniqueCiStatus);
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

    private function deductCIStatus(array $statuses): CheckStatus
    {
        $getStatuses = function (string $statusToFilterOn) {
            return function ($current, $ciStatus) use ($statusToFilterOn) {
                if (null !== $current) {
                    return $current;
                }

                return ($statusToFilterOn === $ciStatus['state']) ? $ciStatus : $current;
            };
        };

        $faillingStatus = array_reduce($statuses, $getStatuses('failure'));
        if ($faillingStatus) {
            return new CheckStatus('RED', $faillingStatus['target_url'] ?? '');
        }

        $supportedStatuses = array_filter($statuses,
            function (array $status) {
                return $this->isStatusSupported($status);
            }
        );

        if (empty($supportedStatuses)) {
            return new CheckStatus('PENDING');
        }

        $successStatus = array_reduce($supportedStatuses, $getStatuses('success'));
        if ($successStatus) {
            return new CheckStatus('GREEN');
        }

        return new CheckStatus('PENDING');
    }

    private function statusesUrl(PRIdentifier $PRIdentifier, string $ref): string
    {
        $matches = GithubAPIHelper::breakoutPRIdentifier($PRIdentifier);
        $matches[2] = $ref;
        $url = sprintf('%s/repos/%s/%s/statuses/%s', $this->domainName, ...$matches);

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

                if ($ad == $bd) {
                    return 0;
                }

                return $ad < $bd ? -1 : 1;
            }
        );

        return $ciStatuses;
    }
}
