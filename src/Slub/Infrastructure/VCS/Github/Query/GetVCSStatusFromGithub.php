<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use GuzzleHttp\Client;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetVCSStatus;
use Slub\Domain\Query\VCSStatus;
use Webmozart\Assert\Assert;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class GetVCSStatusFromGithub implements GetVCSStatus
{
    /** @var Client */
    private $httpClient;

    /** @var string[] */
    private $supportedCIChecks;

    /** @var string */
    private $token;

    public function __construct(Client $httpClient, string $token, string $supportedCIChecks)
    {
        $this->httpClient = $httpClient;
        $this->supportedCIChecks = explode(',', $supportedCIChecks);
        $this->token = $token;
    }

    public function fetch(PRIdentifier $PRIdentifier): VCSStatus
    {
        $PRdetails = $this->fetchPRDetails($PRIdentifier);
        $reviews = $this->fetchReviews($PRdetails);
        $checkSuite = $this->fetchCheckSuite($PRIdentifier, $PRdetails);
        $checkRuns = $this->fetchCheckRuns($checkSuite);
        $result = $this->createVCSStatus($PRIdentifier, $PRdetails, $reviews, $checkSuite, $checkRuns);

        return $result;
    }

    private function createVCSStatus(
        PRIdentifier $PRIdentifier,
        array $PRdetails,
        array $reviews,
        array $checkSuite,
        array $checkRuns
    ): VCSStatus {
        $result = new VCSStatus();
        $result->PRIdentifier = $PRIdentifier->stringValue();
        $result->GTMCount = $this->reviews($reviews, 'approved');
        $result->notGTMCount = $this->reviews($reviews, 'refused');
        $result->comments = $this->reviews($reviews, 'comment');
        $result->CIStatus = $this->ciStatus($checkSuite, $checkRuns);
        $result->isMerged = $this->isMerged($PRdetails);

        return $result;
    }

    private function fetchPRDetails(PRIdentifier $PRIdentifier): array
    {
        $matches = $this->breakoutPRIdentifier($PRIdentifier);
        $url = sprintf('https://api.github.com/repos/%s/%s/pulls/%s', ...$matches);
        $PRdetails = $this->httpClient->get($url, ['headers' => [$this->getToken()]]);

        return json_decode($PRdetails->getBody()->getContents(), true);
    }

    private function fetchReviews(array $PRdetails): array
    {
        $response = $this->httpClient->get($PRdetails['review_comments_url']);
        $content = $response->getBody()->getContents();

        return json_decode($content, true) ?? [];
    }

    private function fetchCheckSuite(PRIdentifier $PRIdentifier, array $PRdetails): array
    {
        $matches = $this->breakoutPRIdentifier($PRIdentifier);
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/commits/%s/check-suites',
            $matches[0],
            $matches[1],
            $PRdetails['base']['sha']
        );
        $checkSuite = $this->httpClient->get(
            $url,
            [
                'headers' => [
                    'Authorization' => $this->getToken(),
                    'Accept' => 'application/vnd.github.antiope-preview+json',
                ],
            ]
        );

        return json_decode($checkSuite->getBody()->getContents(), true) ?? [];
    }

    private function reviews(array $reviews, string $status): int
    {
        return count(
            array_filter(
                $reviews,
                function (array $review) use ($status) {
                    return $review['state'] === $status;
                }
            )
        );
    }

    private function ciStatus(array $checkSuite, array $checkRuns): string
    {
        if ('failure' === $checkSuite['check_suites'][0]['status']) {
            return 'RED';
        }

        $result = array_reduce(
            $checkRuns['check_runs'],
            function (string $current, array $ciStatus) {
                if (!in_array($ciStatus['description'], $this->supportedCIChecks)
                    || 'completed' !== $ciStatus['status']
                ) {
                    return $current;
                }

                if ('success' === $ciStatus['state']) {
                    return 'GREEN';
                }

                return $current;
            },
            'PENDING'
        );

        return $result;
    }

    private function isMerged(array $PRdetails): bool
    {
        return 'closed' === $PRdetails['state'];
    }

    private function breakoutPRIdentifier(PRIdentifier $PRIdentifier): array
    {
        preg_match('/(.+)\/(.+)\/(.+)/', $PRIdentifier->stringValue(), $matches);
        array_shift($matches);
        Assert::count($matches, 3);

        return $matches;
    }

    private function getToken(): string
    {
        return sprintf('token %s', $this->token);
    }

    private function fetchCheckRuns(array $checkSuite): array
    {
        $checkRuns = $this->httpClient->get(
            $checkSuite['check_suites'][0]['check_runs_url'],
            [
                'headers' => [
                    'Authorization' => $this->getToken(),
                    'Accept' => 'application/vnd.github.antiope-preview+json',
                ],
            ]
        );

        return json_decode($checkRuns->getBody()->getContents(), true) ?? [];
    }
}
