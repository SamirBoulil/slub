<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query;

use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;
use Slub\Infrastructure\VCS\Github\Query\GetPRDetails;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetPRDetailsTest extends WebTestCase
{
    public const REPOSITORY_NAME = 'SamirBoulil/slub';
    public const PR_NUMBER = '36';

    private GetPRDetails $getPRDetails;

    private GithubAPIClient|ObjectProphecy $githubAPIClient;

    public function setUp(): void
    {
        parent::setUp();
        $this->githubAPIClient = $this->prophesize(GithubAPIClient::class);
        $this->getPRDetails = new GetPRDetails($this->githubAPIClient->reveal());
    }

    /**
     * @test
     */
    public function it_successfully_fetches_the_PR_Details(): void
    {
        $expectedReviews = ['info' => 'one'];

        $this->githubAPIClient->get(
            sprintf('https://api.github.com/repos/%s/pulls/%s', self::REPOSITORY_NAME, self::PR_NUMBER),
            [],
            self::REPOSITORY_NAME
        )->willReturn(new Response(200, [], (string) json_encode($expectedReviews)));

        $actualReviews = $this->getPRDetails->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'));

        $this->assertEquals($expectedReviews, $actualReviews);
    }

    /**
     * @test
     */
    public function it_throws_if_the_response_is_malformed(): void
    {
        $this->githubAPIClient->get(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(200, [], '{'));
        $this->expectException(\RuntimeException::class);

        $this->getPRDetails->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'));
    }

    /**
     * @test
     */
    public function it_throws_if_the_response_is_not_successfull(): void
    {
        $this->githubAPIClient->get(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(400, [], '{}'));
        $this->expectException(\RuntimeException::class);

        $this->getPRDetails->fetch(PRIdentifier::fromString('SamirBoulil/slub/36'));
    }
}
