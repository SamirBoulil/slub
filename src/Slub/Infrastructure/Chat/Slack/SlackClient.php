<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SlackClient implements ChatClient
{
    private GetBotUserId $getBotUserId;

    private GetBotReactionsForMessageAndUser $getBotReactionsForMessageAndUser;

    private ClientInterface $client;

    private LoggerInterface $logger;

    private SqlSlackAppInstallationRepository $slackAppInstallationRepository;

    public function __construct(
        GetBotUserId $getBotUserId,
        GetBotReactionsForMessageAndUser $getBotReactionsForMessageAndUser,
        ClientInterface $client,
        LoggerInterface $logger,
        SqlSlackAppInstallationRepository $slackAppInstallationRepository
    ) {
        $this->getBotUserId = $getBotUserId;
        $this->getBotReactionsForMessageAndUser = $getBotReactionsForMessageAndUser;
        $this->client = $client;
        $this->slackAppInstallationRepository = $slackAppInstallationRepository;
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
                        'Authorization' => 'Bearer ' . $this->slackToken($message['workspace']),
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
        $this->logger->critical(implode(',', $currentReactions));
        $reactionsToRemove = array_diff($currentReactions, $reactionsToSet);
        $reactionsToAdd = array_diff($reactionsToSet, $currentReactions);
        $this->removeReactions($messageIdentifier, $reactionsToRemove);
        $this->addReactions($messageIdentifier, $reactionsToAdd);
    }

    public function publishInChannel(ChannelIdentifier $channelIdentifier, string $text): void
    {
        $channel = ChannelIdentifierHelper::split($channelIdentifier->stringValue());
        APIHelper::checkResponse(
            $this->client->post(
                'https://slack.com/api/chat.postMessage',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->slackToken($channel['workspace']),
                        'Content-type' => 'application/json; charset=utf-8',
                    ],
                    'json' => [
                        'channel' => $channel['channel'],
                        'text' => $text,
                    ],
                ]
            )
        );
    }

    private function getCurrentReactions(MessageIdentifier $messageIdentifier): array
    {
        $messageId = MessageIdentifierHelper::split($messageIdentifier->stringValue());
        $botUserId = $this->getBotUserId->fetch($messageId['workspace']);

        $this->logger->critical(sprintf('Fetching reactions for workspace "%s", channel "%s", message "%s"', ...array_values($messageId)));
        $this->logger->critical(sprintf('bot Id is "%s"', $botUserId));

        $result = $this->getBotReactionsForMessageAndUser->fetch(
            $messageId['workspace'],
            $messageId['channel'],
            $messageId['ts'],
            $botUserId
        );

        $this->logger->critical(sprintf('Reactions: %s', implode(',', $result)));

        return $result;
    }

    private function addReactions(MessageIdentifier $messageIdentifier, array $reactionsToAdd): void
    {
        $message = MessageIdentifierHelper::split($messageIdentifier->stringValue());
        foreach ($reactionsToAdd as $reactionToAdd) {
            $this->client->post(
                'https://slack.com/api/reactions.add',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '. $this->slackToken($message['workspace']),
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
                        'Authorization' => 'Bearer '.$this->slackToken($message['workspace']),
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

    private function slackToken(string $workspaceId): string
    {
        return $this->slackAppInstallationRepository->getBy($workspaceId)->accessToken;
    }
}
