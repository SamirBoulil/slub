<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Slub\Application\NotifySquad\ChatClient;
use Slub\Domain\Entity\PR\MessageIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class SlackClient implements ChatClient
{
    /** @var Client */
    private $client;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function replyInThread(MessageIdentifier $messageIdentifier, string $text): void
    {
        $message = MessageIdentifierHelper::split($messageIdentifier->stringValue());
        $response = $this->client->post(
            '/api/chat.postMessage',
            [
                'json' => [
                    'channel'   => $message['channel'],
                    'thread_ts' => $message['ts'],
                    'text'      => $text,
                ],
            ]
        );
        $this->logger->critical($response->getStatusCode());
        $this->logger->critical($response->getBody()->getContents());
    }
}
