<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 *
 * // TODO: Extract a read model Reviews from this class
 */
class FindReviews
{
    public const GTMS = 'GTMS';
    public const NOT_GTMS = 'NOT_GTMS';
    public const COMMENTS = 'COMMENTS';

    private const APPROVED = 'APPROVED';
    private const REFUSED = 'REFUSED';
    private const COMMENTED = 'COMMENTED';

    private GithubAPIClient $githubAPIClient;
    private string $githubURI;

    public function __construct(GithubAPIClient $githubAPIClient, string $githubURI)
    {
        $this->githubAPIClient = $githubAPIClient;
        $this->githubURI = $githubURI;
    }

    public function fetch(PRIdentifier $PRIdentifier): array
    {
        $reviews = $this->reviews($PRIdentifier);

        return [
            self::GTMS => $this->count($reviews, self::APPROVED),
            self::NOT_GTMS => $this->count($reviews, self::REFUSED),
            self::COMMENTS => $this->count($reviews, self::COMMENTED),
        ];
    }

    private function reviews(PRIdentifier $PRIdentifier): array
    {
        $url = $this->getUrl($PRIdentifier);
        $repositoryIdentifier = GithubAPIHelper::repositoryIdentifierFrom($PRIdentifier);
        $response = $this->githubAPIClient->get($url, [], $repositoryIdentifier);

        $content = json_decode($response->getBody()->getContents(), true);

        if (200 !== $response->getStatusCode() || null === $content) {
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

    private function getUrl(PRIdentifier $PRIdentifier): string
    {
        return sprintf(
            '%s/repos/%s/pulls/%s/reviews',
            $this->githubURI,
            GithubAPIHelper::repositoryIdentifierFrom($PRIdentifier),
            GithubAPIHelper::PRNumber($PRIdentifier)
        );
    }

    private function count(array $reviews, string $status): int
    {
        return count(
            array_filter(
                $reviews,
                fn (array $review) => $review['state'] === $status
            )
        );
    }
}
