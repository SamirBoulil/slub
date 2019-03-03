<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;
use Slub\Application\NotifySquad\ChatClient;
use Slub\Domain\Entity\PR\MessageIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class SlackClient implements ChatClient
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function replyInThread(MessageIdentifier $messageIdentifier, string $text): void
    {
        $message = MessageIdentifierHelper::split($messageIdentifier->stringValue());
        $this->client->post(
            '/chat.postMessage',
            [
                'json' => [
                    'channel'   => $message['channel'],
                    'thread_ts' => $message['ts'],
                    'text'      => $text,
                ],
            ]
        );
    }
}
