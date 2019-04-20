<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\PR\MessageIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class SlackClient implements ChatClient
{
    /** @var Client */
    private $client;

    /** @var string */
    private $slackToken;

    public function __construct(Client $client, string $slackToken)
    {
        $this->client = $client;
        $this->slackToken = $slackToken;
    }

    public function replyInThread(MessageIdentifier $messageIdentifier, string $text): void
    {
        $message = MessageIdentifierHelper::split($messageIdentifier->stringValue());
        $this->checkResponse(
            $this->client->post(
                'https://slack.com/api/chat.postMessage',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->slackToken,
                        'Content-type' => 'application/json; charset=utf-8',
                    ],
                    'json' => [
                        'thread_ts' => $message['ts'],
                        'channel' => $message['channel'],
                        'text' => $text,
                    ],
                ]
            )
        );
    }

    public function reactToMessageWith(MessageIdentifier $messageIdentifier, string $emoji): void
    {
        $message = MessageIdentifierHelper::split($messageIdentifier->stringValue());
        $this->client->post(
            'https://slack.com/api/reactions.add',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->slackToken,
                    'Content-type' => 'application/json; charset=utf-8',
                ],
                'json' => [
                    'channel' => $message['channel'],
                    'timestamp' => $message['ts'],
                    'name' => $emoji,
                ],
            ]
        );
    }

    private function checkResponse(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();
        $contents = json_decode($response->getBody()->getContents(), true);
        $hasError = 200 !== $statusCode || false === $contents['ok'];

        if ($hasError) {
            throw new \RuntimeException(
                sprintf(
                    'There was an issue when sending a message to slack (status %d): "%s"',
                    $statusCode,
                    json_encode($contents)
                )
            );
        }
    }
}
