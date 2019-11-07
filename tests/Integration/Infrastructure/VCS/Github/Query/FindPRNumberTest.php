<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query;

use GuzzleHttp\Psr7\Response;
use Slub\Infrastructure\VCS\Github\Query\FindPRNumber;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class FindPRNumberTest extends WebTestCase
{
    private const AUTH_TOKEN = 'TOKEN';
    private const REPOSITORY_NAME = 'samirboulil/slub';
    private const COMMIT_SHA = 'commit_sha';
    private const PR_NUMBER = '1234';

    /** @var FindPRNumber */
    private $findPRNumber;

    /** @var GuzzleSpy */
    private $requestSpy;

    public function setUp(): void
    {
        parent::setUp();
        $this->requestSpy = new GuzzleSpy();
        $this->findPRNumber = new FindPRNumber($this->requestSpy->client(), self::AUTH_TOKEN);
    }

    /**
     * @test
     */
    public function it_finds_a_pr_number_given_a_repository_and_a_commit_sha()
    {
        $expectedPRNumber = self::PR_NUMBER;
        $this->requestSpy->stubResponse(new Response(200, [], $this->successfullyFindsAPR()));

        $actualPRNumber = $this->findPRNumber->fetch(self::REPOSITORY_NAME, self::COMMIT_SHA);

        $this->assertEquals($expectedPRNumber, $actualPRNumber);
    }

    /**
     * @test
     */
    public function it_does_not_find_a_pr_number_given_a_repository_and_a_commit_sha()
    {
        $this->requestSpy->stubResponse(new Response(200, [], $this->noResult()));

        $actualPRNumber = $this->findPRNumber->fetch(self::REPOSITORY_NAME, self::COMMIT_SHA);

        $this->assertNull($actualPRNumber);
    }

    /**
     * @test
     */
    public function it_cannot_find_the_pr_number_in_the_search_result()
    {
        $this->requestSpy->stubResponse(new Response(200, [], $this->invalidResult()));

        $actualPRNumber = $this->findPRNumber->fetch(self::REPOSITORY_NAME, self::COMMIT_SHA);

        $this->assertNull($actualPRNumber);
    }

    private function successfullyFindsAPR(): string
    {
        return (string) json_encode(
            [
                'items' => [
                    [
                        'pull_request' => [
                            'url' => 'https://api.github.com/whatever/path/it/is/the/number/is/always/at/the/end/' . self::PR_NUMBER,
                        ],
                    ],
                ],
            ]
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
