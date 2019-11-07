<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query;

use GuzzleHttp\Psr7\Response;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\GetPRDetails;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetPRDetailsTest extends WebTestCase
{
    private const AUTH_TOKEN = 'TOKEN';

    /** @var GetPRDetails */
    private $getPRDetails;

    /** @var GuzzleSpy */
    private $requestSpy;

    public function setUp(): void
    {
        parent::setUp();
        $this->requestSpy = new GuzzleSpy();
        $this->getPRDetails = new GetPRDetails($this->requestSpy->client(), self::AUTH_TOKEN);
    }

    /**
     * @test
     */
    public function it_successfully_fetches_the_PR_Details(): void
    {
        $expectedReviews = ['info' => 'one'];
        $this->requestSpy->stubResponse(new Response(200, [], (string) json_encode($expectedReviews)));

        $actualReviews = $this->getPRDetails->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'));

        $this->assertEquals($expectedReviews, $actualReviews);
        $generatedRequest = $this->requestSpy->getRequest();
        $this->requestSpy->assertMethod('GET', $generatedRequest);
        $this->requestSpy->assertURI('/repos/SamirBoulil/slub/pulls/36', $generatedRequest);
        $this->requestSpy->assertAuthToken(self::AUTH_TOKEN, $generatedRequest);
        $this->requestSpy->assertContentEmpty($generatedRequest);
    }

    /**
     * @test
     */
    public function it_throws_if_the_response_is_malformed(): void
    {
        $this->requestSpy->stubResponse(new Response(200, [], '{'));
        $this->expectException(\RuntimeException::class);

        $this->getPRDetails->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'));
    }
}
