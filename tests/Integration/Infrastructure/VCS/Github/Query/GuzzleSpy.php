<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GuzzleSpy
{
    /** @var Client */
    private $client;

    /** @var MockHandler */
    private $mockHandler;

    public function __construct()
    {
        $this->mockHandler = new MockHandler([]);
        $handler = HandlerStack::create($this->mockHandler);
        $this->client = new Client(['handler' => $handler]);
    }

    public function client(): Client
    {
        return $this->client;
    }

    public function stubResponse(Response $response): void
    {
        $this->mockHandler->append($response);
    }

    public function assertAuthToken(string $authToken, RequestInterface $generatedRequest): void
    {
        Assert::assertEquals('token ' . $authToken, current($generatedRequest->getHeaders()['Authorization']));
    }

    public function assertURI(string $uri, RequestInterface $generatedRequest): void
    {
        Assert::assertEquals($uri, $generatedRequest->getUri()->getPath());
    }

    public function assertMethod(string $method, RequestInterface $generatedRequest): void
    {
        Assert::assertEquals($method, $generatedRequest->getMethod());
    }

    public function assertContentEmpty(RequestInterface $generatedRequest): void
    {
        Assert::assertEmpty($generatedRequest->getBody()->getContents());
    }

    public function getRequest(): RequestInterface
    {
        return $this->mockHandler->getLastRequest();
    }
}
