<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query;

use GuzzleHttp\Psr7\Response;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\FindReviews;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>

 */
class FindReviewsTest extends WebTestCase
{
    private const AUTH_TOKEN = 'TOKEN';

    /** @var FindReviews */
    private $findReviews;

    /** @var GuzzleSpy */
    private $requestSpy;

    public function setUp(): void
    {
        parent::setUp();
        $this->requestSpy = new GuzzleSpy();
        $this->findReviews = new FindReviews($this->requestSpy->client(), self::AUTH_TOKEN);
    }

    /**
     * @test
     * @dataProvider reviewsExamples
     */
    public function it_successfully_fetches_the_reviews(array $someReviews, array $expectedCounts): void
    {
        $this->requestSpy->stubResponse(new Response(200, [], (string) json_encode($someReviews)));

        $actualReviews = $this->findReviews->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'));

        $this->assertEquals($expectedCounts, $actualReviews);
        $generatedRequest = $this->requestSpy->getRequest();
        $this->requestSpy->assertMethod('GET', $generatedRequest);
        $this->requestSpy->assertURI('/repos/SamirBoulil/slub/pulls/36/reviews', $generatedRequest);
        $this->requestSpy->assertAuthToken(self::AUTH_TOKEN, $generatedRequest);
        $this->requestSpy->assertContentEmpty($generatedRequest);
    }

    public function reviewsExamples(): array
    {
        return [
            'No reviews' => [[], ['GTMS' => 0, 'NOT_GTMS' => 0, 'COMMENTS' => 0]],
            'Some reviews' => [
                [['state' => 'APPROVED'], ['state' => 'REFUSED'], ['state' => 'COMMENTED']],
                ['GTMS' => 1, 'NOT_GTMS' => 1, 'COMMENTS' => 1]
            ]
        ];
    }

    /**
     * @test
     */
    public function it_throws_if_the_response_is_malformed(): void
    {
        $this->requestSpy->stubResponse(new Response(200, [], '{'));
        $this->expectException(\RuntimeException::class);

        $this->findReviews->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'));
    }
}
