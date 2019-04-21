<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\PR\MessageIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class SlackClient implements ChatClient
{
    /** @var GetBotUserId */
    private $getBotUserId;

    /** @var GetBotReactionsForMessageAndUser */
    private $getBotReactionsForMessageAndUser;

    /** @var Client */
    private $client;

    /** @var string */
    private $slackToken;

    /** @var string */
    private $slackBotUserId; // TODO: remove,  call slack API

    public function __construct(
        GetBotUserId $getBotUserId,
        GetBotReactionsForMessageAndUser $getBotReactionsForMessageAndUser,
        Client $client,
        string $slackToken,
        string  $slackBotUserId
    ) {
        $this->getBotUserId = $getBotUserId;
        $this->getBotReactionsForMessageAndUser = $getBotReactionsForMessageAndUser;
        $this->client = $client;
        $this->slackToken = $slackToken;
        $this->slackBotUserId = $slackBotUserId;
    }

    public function replyInThread(MessageIdentifier $messageIdentifier, string $text): void
    {
        $message = MessageIdentifierHelper::split($messageIdentifier->stringValue());
        APIHelper::checkResponse(
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

    public function setReactionsToMessageWith(MessageIdentifier $messageIdentifier, array $reactionsToSet): void
    {
        $currentReactions = $this->getCurrentReactions($messageIdentifier);
        $reactionsToRemove = array_diff($currentReactions, $reactionsToSet);
        $reactionsToAdd = array_diff($reactionsToSet, $currentReactions);
        $this->removeReactions($messageIdentifier, $reactionsToRemove);
        $this->addReactions($messageIdentifier, $reactionsToAdd);
    }

    private function getCurrentReactions(MessageIdentifier $messageIdentifier): array
    {
        $messageId = MessageIdentifierHelper::split($messageIdentifier->stringValue());
        $botUserId = $this->slackBotUserId;

        return $this->getBotReactionsForMessageAndUser->fetch($messageId['channel'], $messageId['ts'], $botUserId);
    }

    private function addReactions(MessageIdentifier $messageIdentifier, array $reactionsToAdd): void
    {
        $message = MessageIdentifierHelper::split($messageIdentifier->stringValue());
        foreach ($reactionsToAdd as $reactionToAdd) {
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
                        'name' => $reactionToAdd,
                    ],
                ]
            );
        }
    }

    private function removeReactions(MessageIdentifier $messageIdentifier, array $reactionsToRemove): void
    {
        $message = MessageIdentifierHelper::split($messageIdentifier->stringValue());
        foreach ($reactionsToRemove as $reactionToRemove) {
            $this->client->post(
                'https://slack.com/api/reactions.remove',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->slackToken,
                        'Content-type' => 'application/json; charset=utf-8',
                    ],
                    'json' => [
                        'channel' => $message['channel'],
                        'timestamp' => $message['ts'],
                        'name' => $reactionToRemove,
                    ],
                ]
            );
        }
    }
}
