<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Application\PutPRToReview\PutPRToReview;
use Slub\Application\PutPRToReview\PutPRToReviewHandler;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ProcessTRRequestListener
{
    private PutPRToReviewHandler $putPRToReviewHandler;
    private GetChannelInformationInterface $getChannelInformation;
    private GetPRInfoInterface $getPRInfo;
    private ChatClient $chatClient;
    private RouterInterface $router;
    private LoggerInterface $logger;

    public function __construct(
        PutPRToReviewHandler $putPRToReviewHandler,
        GetChannelInformationInterface $getChannelInformation,
        GetPRInfoInterface $getPRInfo,
        ChatClient $chatClient,
        RouterInterface $router,
        LoggerInterface $logger
    ) {
        $this->putPRToReviewHandler = $putPRToReviewHandler;
        $this->getChannelInformation = $getChannelInformation;
        $this->getPRInfo = $getPRInfo;
        $this->chatClient = $chatClient;
        $this->router = $router;
        $this->logger = $logger;
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $currentRoute = $this->router->match($request->getPathInfo());
        if ('chat_slack_tr' === $currentRoute['_route']) {
            $this->processTR($request);
        }
    }

    private function processTR(Request $request): void
    {
        try {
            $PRIdentifier = $this->extractPRIdentifierFromSlackCommand($request->request->get('text'));
        } catch (ImpossibleToParseGithubURL $exception) {
            $this->explainAuthorURLCannotBeParsed($request);

            return;
        }
        try {
            $this->logger->critical('New PR has been put to review');
            $PRInfo = $this->getPRInfo->fetch($PRIdentifier);
            $workspaceIdentifier = $this->getWorkspaceIdentifier($request);
            $channelIdentifier = $this->getChannelIdentifier($request);
            $authorIdentifier = $this->getAuthorIdentifier($request);
            $messageIdentifier = $this->publishToReviewAnnouncement($PRInfo, $channelIdentifier, $authorIdentifier);
            $this->putPRToReview(
                $PRInfo,
                $workspaceIdentifier,
                $channelIdentifier,
                $authorIdentifier,
                $messageIdentifier
            );
        } catch (\Exception | \Error $e) {
            $this->logger->error(sprintf('An error occurred during a TR submission: %s', $e->getMessage()));
            $this->explainAuthorPRCouldNotBeSubmittedToReview($request);
        }
    }

    private function getWorkspaceIdentifier(Request $request): string
    {
        return $request->request->get('team_id');
    }

    private function getAuthorIdentifier(Request $request): string
    {
        return $request->request->get('user_id');
    }

    private function getChannelIdentifier(Request $request): string
    {
        $workspace = $this->getWorkspaceIdentifier($request);
        $channel = $request->request->get('channel_id');
        $channelName = $this->channelName($workspace, $channel);

        return ChannelIdentifierHelper::from($workspace, $channelName);
    }

    private function channelName(string $workspace, string $channel): string
    {
        return $this->getChannelInformation->fetch($workspace, $channel)->channelName;
    }

    private function publishToReviewAnnouncement(
        PRInfo $PRInfo,
        string $channelIdentifier,
        string $authorIdentifier
    ): string {
        // TODO: Consider putting the url in the PRInfo class instead
        $PRUrl = GithubAPIHelper::PRPageUrl(PRIdentifier::fromString($PRInfo->PRIdentifier));

        $message = [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => sprintf(
                        '<@%s> needs GTMs for <%s|%s>',
                        $authorIdentifier,
                        $PRUrl,
                        $PRInfo->title
                    ),
                ],
            ],
        ];

        return $this->chatClient->publishMessageWithBlocksInChannel(
            ChannelIdentifier::fromString($channelIdentifier),
            $message
        );
    }

    private function putPRToReview(
        PRInfo $PRInfo,
        string $workspaceIdentifier,
        string $channelIdentifier,
        string $authorIdentifier,
        string $messageIdentifier
    ): void {
        $PRToReview = new PutPRToReview();
        $PRToReview->PRIdentifier = $PRInfo->PRIdentifier;
        $PRToReview->repositoryIdentifier = $PRInfo->repositoryIdentifier;
        $PRToReview->channelIdentifier = $channelIdentifier;
        $PRToReview->workspaceIdentifier = $workspaceIdentifier;
        $PRToReview->messageIdentifier = $messageIdentifier;
        $PRToReview->authorIdentifier = $authorIdentifier;
        $PRToReview->title = $PRInfo->title;
        $PRToReview->GTMCount = $PRInfo->GTMCount;
        $PRToReview->notGTMCount = $PRInfo->notGTMCount;
        $PRToReview->comments = $PRInfo->comments;
        $PRToReview->CIStatus = $PRInfo->CIStatus->status;
        $PRToReview->isMerged = $PRInfo->isMerged;
        $PRToReview->isClosed = $PRInfo->isClosed;
        $PRToReview->additions = $PRInfo->additions;
        $PRToReview->deletions = $PRInfo->deletions;

        $this->logger->debug(
            sprintf(
                'New PR to review - workspace "%s" - channel "%s" - repository "%s" - author "%s" - message "%s" - PR "%s".',
                $PRToReview->workspaceIdentifier,
                $PRToReview->channelIdentifier,
                $PRToReview->repositoryIdentifier,
                $PRToReview->authorIdentifier,
                $PRToReview->messageIdentifier,
                $PRToReview->PRIdentifier
            )
        );

        $this->putPRToReviewHandler->handle($PRToReview);
    }

    private function extractPRIdentifierFromSlackCommand(string $text): PRIdentifier
    {
        try {
            preg_match('#<https://github.com/(.*)/pull/(\d+).*>$#', $text, $matches);
            [$repositoryIdentifier, $PRNumber] = ([$matches[1], $matches[2]]);
            Assert::stringNotEmpty($repositoryIdentifier, $PRNumber);
            Assert::stringNotEmpty($PRNumber, $PRNumber);
            $PRIdentifier = PRIdentifier::fromString(
                GithubAPIHelper::PRIdentifierFrom($repositoryIdentifier, $PRNumber)
            );
        } catch (\Exception $e) {
            throw new ImpossibleToParseGithubURL($text);
        }

        return $PRIdentifier;
    }

    private function explainAuthorURLCannotBeParsed(Request $request): void
    {
        $authorInput = $request->request->get('text');
        $responseUrl = $request->request->get('response_url');
        $text = <<<SLACK
:warning: `/tr %s`
:thinking_face: Sorry, I was not able to parse the pull request URL, can you check it and try again ?
SLACK;
        $this->chatClient->answerWithEphemeralMessage($responseUrl, sprintf($text, $authorInput));
    }

    private function explainAuthorPRCouldNotBeSubmittedToReview(Request $request)
    {
        $authorInput = $request->request->get('text');
        $responseUrl = $request->request->get('response_url');
        $text = <<<SLACK
:warning: `/tr %s`

:thinking_face: Something went wrong, I was not able to put your PR to Review.

Can you check the pull request URL ? If this issue keeps coming, Slack @SamirBoulil.
SLACK;
        $this->chatClient->answerWithEphemeralMessage($responseUrl, sprintf($text, $authorInput));
    }
}
