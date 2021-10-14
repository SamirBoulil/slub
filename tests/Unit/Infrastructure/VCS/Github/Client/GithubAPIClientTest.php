<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\Client;

use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Infrastructure\VCS\Github\Client\GithubAppInstallation;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlAppInstallationRepository;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;
use Slub\Infrastructure\VCS\Github\Client\RefreshAccessToken;
use Tests\Integration\Infrastructure\KernelTestCase;
use Tests\Integration\Infrastructure\VCS\Github\Query\GuzzleSpy;

class GithubAPIClientTest extends KernelTestCase
{
    private const REPOSITORY_IDENTIFIER = 'samirboulil/slub';
    private const ACCESS_TOKEN = '1234cZA12';
    const INSTALLATION_ID = '1234';

    /** @var GuzzleSpy */
    private $requestSpy;

    /** @var ObjectProphecy|RefreshAccessToken */
    private $refreshAccessToken;

    /** @var ObjectProphecy|SqlAppInstallationRepository */
    private $sqlAppInstallationRepository;

    /** @var GithubAPIClient */
    private $githubAPIClient;

    public function setUp(): void
    {
        parent::setUp();
        $this->requestSpy = new GuzzleSpy();
        $this->refreshAccessToken = $this->prophesize(RefreshAccessToken::class);
        $this->sqlAppInstallationRepository = $this->prophesize(SqlAppInstallationRepository::class);
        $this->githubAPIClient = new GithubAPIClient(
            $this->refreshAccessToken->reveal(),
            $this->sqlAppInstallationRepository->reveal(),
            $this->requestSpy->client()
        );
    }

    /** @test */
    public function it_fetches_the_access_token_to_call_the_github_api(): void
    {
        $url = 'https://github.com/some_endpoint';
        $responseContent = 'some_answer';
        $appInstallation = $this->appInstallation();

        $this->sqlAppInstallationRepository->getBy(self::REPOSITORY_IDENTIFIER)
             ->willReturn($appInstallation);
        $this->requestSpy->stubResponse(new Response(200, [], $responseContent));

        $response = $this->githubAPIClient->get($url, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals($responseContent, $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());

        $request = $this->requestSpy->getRequest();
        $this->requestSpy->assertURI('/some_endpoint', $request);
        $this->requestSpy->assertContentEmpty($request);
        $this->requestSpy->assertAuthToken('1234cZA12', $request);
    }

    /** @test */
    public function it_refreshes_and_saves_the_access_token_when_it_expires_before_calling_the_github_api(): void
    {
        $url = 'https://github.com/some_endpoint';
        $responseContent = 'some_answer';
        $appInstallation = $this->appInstallation();
        $newAccessToken = 'new_access_token';

        $this->sqlAppInstallationRepository->getBy(self::REPOSITORY_IDENTIFIER)
             ->willReturn($appInstallation);

        // First, unauthorized then authorized
        $this->requestSpy->stubResponse(new Response(401, [], ''));
        $this->requestSpy->stubResponse(new Response(200, [], $responseContent));

        $this->refreshAccessToken->fetch(self::INSTALLATION_ID)->willReturn($newAccessToken);
        $this->sqlAppInstallationRepository->save(
            Argument::that(
                fn (GithubAppInstallation $appInstallation) => $appInstallation->accessToken === $newAccessToken
                    && self::INSTALLATION_ID === $appInstallation->installationId
                    && self::REPOSITORY_IDENTIFIER === $appInstallation->repositoryIdentifier
            )
        )->shouldBeCalled();
        $response = $this->githubAPIClient->get($url, [], self::REPOSITORY_IDENTIFIER);

        self::assertEquals($responseContent, $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());

        $request = $this->requestSpy->getRequest();
        $this->requestSpy->assertURI('/some_endpoint', $request);
        $this->requestSpy->assertContentEmpty($request);
        $this->requestSpy->assertAuthToken($newAccessToken, $request);
    }

    private function appInstallation(): GithubAppInstallation
    {
        $appInstallation = new GithubAppInstallation();
        $appInstallation->repositoryIdentifier = self::REPOSITORY_IDENTIFIER;
        $appInstallation->installationId = self::INSTALLATION_ID;
        $appInstallation->accessToken = self::ACCESS_TOKEN;

        return $appInstallation;
    }
}
