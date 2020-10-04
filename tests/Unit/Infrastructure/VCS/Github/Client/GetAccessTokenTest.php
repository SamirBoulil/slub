<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\Client;

use GuzzleHttp\Psr7\Response;
use Slub\Infrastructure\VCS\Github\Client\GetAccessToken;
use Tests\Integration\Infrastructure\KernelTestCase;
use Tests\Integration\Infrastructure\VCS\Github\Query\GuzzleSpy;

class GetAccessTokenTest extends KernelTestCase
{
    private const MY_APP_ID = 'MY_APP_ID';
    const ACCESS_TOKEN = 'v1.1f699f1069f60xxx';

    /** @var GuzzleSpy */
    private $requestSpy;

    /** @var GetAccessToken */
    private $getAccessToken;

    public function setUp(): void
    {
        parent::setUp();
        $githubPrivateKey = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQC8kGa1pSjbSYZVebtTRBLxBz5H4i2p/llLCrEeQhta5kaQu/Rn
vuER4W8oDH3+3iuIYW4VQAzyqFpwuzjkDI+17t5t0tyazyZ8JXw+KgXTxldMPEL9
5+qVhgXvwtihXC1c5oGbRlEDvDF6Sa53rcFVsYJ4ehde/zUxo6UvS7UrBQIDAQAB
AoGAb/MXV46XxCFRxNuB8LyAtmLDgi/xRnTAlMHjSACddwkyKem8//8eZtw9fzxz
bWZ/1/doQOuHBGYZU8aDzzj59FZ78dyzNFoF91hbvZKkg+6wGyd/LrGVEB+Xre0J
Nil0GReM2AHDNZUYRv+HYJPIOrB0CRczLQsgFJ8K6aAD6F0CQQDzbpjYdx10qgK1
cP59UHiHjPZYC0loEsk7s+hUmT3QHerAQJMZWC11Qrn2N+ybwwNblDKv+s5qgMQ5
5tNoQ9IfAkEAxkyffU6ythpg/H0Ixe1I2rd0GbF05biIzO/i77Det3n4YsJVlDck
ZkcvY3SK2iRIL4c9yY6hlIhs+K9wXTtGWwJBAO9Dskl48mO7woPR9uD22jDpNSwe
k90OMepTjzSvlhjbfuPN1IdhqvSJTDychRwn1kIJ7LQZgQ8fVz9OCFZ/6qMCQGOb
qaGwHmUK6xzpUbbacnYrIM6nLSkXgOAwv7XXCojvY614ILTK3iXiLBOxPu5Eu13k
eUz9sHyD6vkgZzjtxXECQAkp4Xerf5TGfQXGXhxIX52yH+N2LtujCdkQZjXAsGdm
B2zNzvrlgRmgBrklMTrMYgm1NPcW+bRLGcwgW2PTvNM=
-----END RSA PRIVATE KEY-----
EOD;
        $this->requestSpy = new GuzzleSpy();
        $this->getAccessToken = new GetAccessToken($this->requestSpy->client(), self::MY_APP_ID, $githubPrivateKey);
    }

    /** @test */
    public function it_fetches_an_access_token_for_an_app_id()
    {
        $installationId = '123a456b';
        $this->requestSpy->stubResponse(new Response(200, [], $this->accessTokenResponse()));

        $actualAccesstoken = $this->getAccessToken->fetch($installationId);

        self::assertEquals(self::ACCESS_TOKEN, $actualAccesstoken);

        $expectedURI = sprintf('/app/installations/%s/access_tokens', $installationId);
        $actualRequest = $this->requestSpy->getRequest();
        $this->requestSpy->assertURI($expectedURI, $actualRequest);
        $this->requestSpy->assertMethod('GET', $actualRequest);
        self::assertEquals('application/vnd.github.machine-man-preview+json', $actualRequest->getHeader('Accept')[0]);
        $authorization = $actualRequest->getHeader('Authorization')[0];
        self::assertStringStartsWith('Bearer', (string)$authorization);
    }

    private function accessTokenResponse(): string
    {
        return (string) json_encode(['token' => self::ACCESS_TOKEN]);
    }
}
