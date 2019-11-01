<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use GuzzleHttp\Client;
use Slub\Domain\Entity\PR\PRIdentifier;

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

    /** @var Client */
    private $httpClient;

    /** @var string */
    private $authToken;

    public function __construct(Client $httpClient, string $authToken)
    {
        $this->httpClient = $httpClient;
        $this->authToken = $authToken;
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
        $response = $this->httpClient->get($url, ['headers' => GithubAPIHelper::authorizationHeader($this->authToken)]);

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

    private function getUrl(PRIdentifier $PRIdentifier): string
    {
        $matches = GithubAPIHelper::breakoutPRIdentifier($PRIdentifier);
        $url = sprintf('https://api.github.com/repos/%s/%s/pulls/%s/reviews', ...$matches);

        return $url;
    }

    private function count(array $reviews, string $status): int
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
}
