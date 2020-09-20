<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SlackClient implements ChatClient
{
    /** @var GetBotUserId */
    private $getBotUserId;

    /** @var GetBotReactionsForMessageAndUser */
    private $getBotReactionsForMessageAndUser;

    /** @var Client */
    private $client;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $slackToken;

    /** @var string */
    private $slackBotUserId;  // TODO: remove,  call slack API

    public function __construct(
        GetBotUserId $getBotUserId,
        GetBotReactionsForMessageAndUser $getBotReactionsForMessageAndUser,
        Client $client,
        LoggerInterface $logger,
        string $slackToken,
        string $slackBotUserId
    ) {
        $this->getBotUserId = $getBotUserId;
        $this->getBotReactionsForMessageAndUser = $getBotReactionsForMessageAndUser;
        $this->client = $client;
        $this->slackToken = $slackToken;
        $this->slackBotUserId = $slackBotUserId;
        $this->logger = $logger;
    }

    public function replyInThread(MessageIdentifier $messageIdentifier, string $text): void
    {
        $message = MessageIdentifierHelper::split($messageIdentifier->stringValue());
        APIHelper::checkResponse(
            $this->client->post(
                'https://slack.com/api/chat.postMessage',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->slackToken,
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

    public function publishInChannel(ChannelIdentifier $channelIdentifier, string $text): void
    {
        APIHelper::checkResponse(
            $this->client->post(
                'https://slack.com/api/chat.postMessage',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->slackToken,
                        'Content-type' => 'application/json; charset=utf-8',
                    ],
                    'json' => [
                        'channel' => $channelIdentifier->stringValue(),
                        'text' => $text,
                    ],
                ]
            )
        );
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
                        'Authorization' => 'Bearer '.$this->slackToken,
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
        $this->logger->critical(
            sprintf(
                'Updating reactions of "%s", Adding: %s',
                $messageIdentifier->stringValue(),
                implode(',', $reactionsToAdd)
            )
        );
    }

    private function removeReactions(MessageIdentifier $messageIdentifier, array $reactionsToRemove): void
    {
        $message = MessageIdentifierHelper::split($messageIdentifier->stringValue());
        foreach ($reactionsToRemove as $reactionToRemove) {
            $this->client->post(
                'https://slack.com/api/reactions.remove',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->slackToken,
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
        $this->logger->critical(
            sprintf(
                'Updating reactions of "%s", Removing: %s',
                $messageIdentifier->stringValue(),
                implode(',', $reactionsToRemove)
            )
        );
    }
}
