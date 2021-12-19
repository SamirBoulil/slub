<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query;

use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;
use Slub\Infrastructure\VCS\Github\Query\FindPRNumber;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class FindPRNumberTest extends WebTestCase
{
    private const REPOSITORY_NAME = 'samirboulil/slub';
    private const COMMIT_SHA = 'commit_sha';
    private const PR_NUMBER = '1234';

    /** @var FindPRNumber */
    private $findPRNumber;

    /** @var ObjectProphecy|GithubAPIClient */
    private $githubAPIClient;

    public function setUp(): void
    {
        parent::setUp();
        $this->githubAPIClient = $this->prophesize(GithubAPIClient::class);
        $this->findPRNumber = new FindPRNumber($this->githubAPIClient->reveal());
    }

    /**
     * @test
     */
    public function it_finds_a_pr_number_given_a_repository_and_a_commit_sha(): void
    {
        $expectedPRNumber = self::PR_NUMBER;
        $this->githubAPIClient->get(
            sprintf('https://api.github.com/search/issues?q=%s+%s', self::REPOSITORY_NAME, self::COMMIT_SHA),
            [],
            self::REPOSITORY_NAME
        )->willReturn(new Response(200, [], $this->successfullyFindsAPR()));

        $actualPRNumber = $this->findPRNumber->fetch(self::REPOSITORY_NAME, self::COMMIT_SHA);

        $this->assertEquals($expectedPRNumber, $actualPRNumber);
    }

    /**
     * @test
     */
    public function it_does_not_find_a_pr_number_given_a_repository_and_a_commit_sha(): void
    {
        $this->githubAPIClient->get(
            sprintf('https://api.github.com/search/issues?q=%s+%s', self::REPOSITORY_NAME, self::COMMIT_SHA),
            [],
            self::REPOSITORY_NAME
        )->willReturn(new Response(200, [], $this->noResult()));

        $actualPRNumber = $this->findPRNumber->fetch(self::REPOSITORY_NAME, self::COMMIT_SHA);

        $this->assertNull($actualPRNumber);
    }

    /**
     * @test
     */
    public function it_cannot_find_the_pr_number_in_the_search_result(): void
    {
        $this->githubAPIClient->get(
            sprintf('https://api.github.com/search/issues?q=%s+%s', self::REPOSITORY_NAME, self::COMMIT_SHA),
            [],
            self::REPOSITORY_NAME
        )->willReturn(new Response(200, [], $this->invalidResult()));

        $actualPRNumber = $this->findPRNumber->fetch(self::REPOSITORY_NAME, self::COMMIT_SHA);

        $this->assertNull($actualPRNumber);
    }

    /**
     * @test
     */
    public function it_throws_if_the_response_is_not_successfull(): void
    {
        $this->githubAPIClient->get(Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Response(400, [], '{}'));
        $this->expectException(\RuntimeException::class);

        $this->findPRNumber->fetch(self::REPOSITORY_NAME, self::COMMIT_SHA);
    }

    private function successfullyFindsAPR(): string
    {
        return (string) json_encode(
            [
                'items' => [
                    [
                        'pull_request' => [
                            'url' => 'https://api.github.com/whatever/path/it/is/the/number/is/always/at/the/end/'.self::PR_NUMBER,
                        ],
                    ],
                ],
            ],
            JSON_THROW_ON_ERROR
        );
    }

    private function noResult(): string
    {
        return (string) json_encode(
            [
                'items' => [],
            ]
        );
    }

    private function invalidResult(): string
    {
        return (string) json_encode(
            [
                'items' => [
                    [
                        'pull_request' => [
                            'url' => 'https://api.github.com/no/pr/number/in/the/url/:(',
                        ],
                    ],
                ],
            ]
        );
    }
}
