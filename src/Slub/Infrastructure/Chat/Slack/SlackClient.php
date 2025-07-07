<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Infrastructure\Chat\Common\ChatHelper;
use Slub\Infrastructure\Chat\Slack\Common\APIHelper;
use Slub\Infrastructure\Chat\Slack\Common\ChannelIdentifierHelper;
use Slub\Infrastructure\Chat\Slack\Common\MessageIdentifierHelper;
use Slub\Infrastructure\Chat\Slack\Query\GetBotReactionsForMessageAndUser;
use Slub\Infrastructure\Chat\Slack\Query\GetBotUserId;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SlackClient implements ChatClient
{
    private const MAX_DESCRIPTION = 80;

    public function __construct(private GetBotUserId $getBotUserId, private GetBotReactionsForMessageAndUser $getBotReactionsForMessageAndUser, private ClientInterface $client, private LoggerInterface $logger, private SqlSlackAppInstallationRepository $slackAppInstallationRepository)
    {
    }

    public function replyInThread(MessageIdentifier $messageIdentifier, string $text): void
    {
        // TODO: consider making this one private
        $message = MessageIdentifierHelper::split($messageIdentifier->stringValue());
        APIHelper::checkResponseSuccess(
            $this->client->post(
                'https://slack.com/api/chat.postMessage',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->slackToken($message['workspace']),
                        'Content-type' => 'application/json; charset=utf-8',
                    ],
                    'json' => [
                        'thread_ts' => $message['ts'],
                        'channel' => $message['channel'],
                        'text' => $text,
                        'unfurl_links' => false
                    ],
                ]
            )
        );
    }

    public function setReactionsToMessageWith(MessageIdentifier $messageIdentifier, array $reactionsToSet): void
    {
        $currentReactions = $this->getCurrentReactions($messageIdentifier);
        // $this->logger->critical(implode(',', $currentReactions));
        $reactionsToRemove = array_diff($currentReactions, $reactionsToSet);
        $reactionsToAdd = array_diff($reactionsToSet, $currentReactions);
        $this->removeReactions($messageIdentifier, $reactionsToRemove);
        $this->addReactions($messageIdentifier, $reactionsToAdd);
    }

    public function publishInChannel(ChannelIdentifier $channelIdentifier, string $text): void
    {
        APIHelper::checkResponseSuccess(
            $this->client->post(
                'https://slack.com/api/chat.postMessage',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->slackToken(ChannelIdentifierHelper::workspaceFrom($channelIdentifier)),
                        'Content-type' => 'application/json; charset=utf-8',
                    ],
                    'json' => [
                        'channel' => ChannelIdentifierHelper::channelFrom($channelIdentifier),
                        'text' => $text,
                    ],
                ]
            )
        );
    }

    public function answerWithEphemeralMessage(string $url, string $text): void
    {
        APIHelper::checkStatusCodeSuccess(
            $this->client->post(
                $url,
                [
                    'headers' => [
                        'Content-type' => 'application/json; charset=utf-8',
                    ],
                    'json' => [
                        'text' => $text,
                        'response_type' => 'ephemeral',
                    ],
                ]
            )
        );
    }

    public function publishMessageWithBlocksInChannel(ChannelIdentifier $channelIdentifier, array $blocks): string
    {
        // $this->logger->critical('CHANNEL IDENTIFIER:' . $channelIdentifier->stringValue());
        // $this->logger->critical('workspaceFrom:' . ChannelIdentifierHelper::workspaceFrom($channelIdentifier));
        // $this->logger->critical('channelFrom:' . ChannelIdentifierHelper::channelFrom($channelIdentifier));

        $response = APIHelper::checkResponseSuccess(
            $this->client->post(
                'https://slack.com/api/chat.postMessage',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->slackToken(ChannelIdentifierHelper::workspaceFrom($channelIdentifier)),
                        'Content-type' => 'application/json; charset=utf-8',
                    ],
                    'json' => [
                        'channel' => ChannelIdentifierHelper::channelFrom($channelIdentifier),
                        'blocks' => $blocks,
                        'unfurl_links' => false,
                        'link_names' => true
                    ],
                ]
            )
        );

        return MessageIdentifierHelper::from(
            $response['message']['team'],
            $response['channel'],
            $response['ts']
        );
    }

    public function explainPRURLCannotBeParsed(string $url, string $usage): void
    {
        $text = <<<SLACK
:warning: `%s`
:thinking_face: Sorry, I was not able to parse the pull request URL, can you check it and try again ?
SLACK;
        $message = sprintf($text, $usage);
        $this->answerWithEphemeralMessage($url, $message);
    }

    public function explainAppNotInstalled(string $url, string $usage): void
    {
        $text = <<<SLACK
:warning: `%s`
:thinking_face: It looks like Yeee is not installed on this repository but you can <https://github.com/apps/slub-yeee|Install it> now!
SLACK;
        $this->answerWithEphemeralMessage($url, sprintf($text, $usage));
    }

    public function explainSomethingWentWrong(string $url, string $usage): void
    {
        $text = <<<SLACK
:warning: `%s`

:thinking_face: Something went wrong.

Can you check the pull request URL ? If this issue keeps coming, Send an email at samir.boulil(at)gmail.com.
SLACK;
        $message = sprintf($text, $usage);
        $this->answerWithEphemeralMessage($url, $message);
    }

    public function explainBotNotInChannel(string $url, string $usage): void
    {
        $text = <<<SLACK
:warning: `%s`
:thinking_face: It looks like @Yeee is not a member of this channel. Try inviting him in! :door:
SLACK;
        $message = sprintf($text, $usage);
        $this->answerWithEphemeralMessage($url, $message);
    }

    public function publishToReviewMessage(
        string $channelIdentifier,
        string $PRUrl,
        string $title,
        string $repositoryIdentifier,
        int $additions,
        int $deletions,
        string $authorIdentifier,
        string $authorImageUrl,
        string $description
    ): string {
        $message = [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => sprintf(
                        "*<%s|%s>*\n%s *(+%s -%s)*\n<@%s>\n\n%s",
                        $PRUrl,
                        ChatHelper::escapeHtmlChars(ChatHelper::elipsisIfTooLong($title, self::MAX_DESCRIPTION)),
                        $repositoryIdentifier,
                        $additions,
                        $deletions,
                        $authorIdentifier,
                        ChatHelper::escapeHtmlChars(ChatHelper::elipsisIfTooLong($description, self::MAX_DESCRIPTION))
                    ),
                ],
                'accessory' => [
                    'type' => 'image',
                    'image_url' => $authorImageUrl,
                    'alt_text' => $title,
                ],
            ],
        ];

        return $this->publishMessageWithBlocksInChannel(
            ChannelIdentifier::fromString($channelIdentifier),
            $message
        );
    }

    private function getCurrentReactions(MessageIdentifier $messageIdentifier): array
    {
        $messageId = MessageIdentifierHelper::split($messageIdentifier->stringValue());
        $botUserId = $this->getBotUserId->fetch($messageId['workspace']);

//        $this->logger->critical(
//            sprintf('Fetching reactions for workspace "%s", channel "%s", message "%s"', ...array_values($messageId))
//        );
        // $this->logger->critical(sprintf('bot Id is "%s"', $botUserId));

        $result = $this->getBotReactionsForMessageAndUser->fetch(
            $messageId['workspace'],
            $messageId['channel'],
            $messageId['ts'],
            $botUserId
        );

        // $this->logger->critical(sprintf('Reactions: %s', implode(',', $result)));

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
                        'Authorization' => 'Bearer '.$this->slackToken($message['workspace']),
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
//        $this->logger->critical(
//            sprintf(
//                'Updating reactions of "%s", Adding: %s',
//                $messageIdentifier->stringValue(),
//                implode(',', $reactionsToAdd)
//            )
//        );
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
//        $this->logger->critical(
//            sprintf(
//                'Updating reactions of "%s", Removing: %s',
//                $messageIdentifier->stringValue(),
//                implode(',', $reactionsToRemove)
//            )
//        );
    }

    private function slackToken(string $workspaceId): string
    {
        return $this->slackAppInstallationRepository->getBy($workspaceId)->accessToken;
    }
}
