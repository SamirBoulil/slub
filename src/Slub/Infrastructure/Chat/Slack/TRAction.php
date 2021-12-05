<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use Slub\Application\PutPRToReview\PutPRToReview;
use Slub\Application\PutPRToReview\PutPRToReviewHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class TRAction
{
    private PutPRToReviewHandler $putPRToReviewHandler;
    private GetChannelInformationInterface $getChannelInformation;
    private GetPRInfoInterface $getPRInfo;

    public function __construct(
        PutPRToReviewHandler $putPRToReviewHandler,
        GetChannelInformationInterface $getChannelInformation,
        GetPRInfoInterface $getPRInfo
    ) {
        $this->putPRToReviewHandler = $putPRToReviewHandler;
        $this->getChannelInformation = $getChannelInformation;
        $this->getPRInfo = $getPRInfo;
    }

    public function executeAction(Request $request): Response
    {
        $PRInfo = $this->PRInfo($request);
        $workspaceIdentifier = $this->getWorkspaceIdentifier($request);
        $channelIdentifier = $this->getChannelIdentifier($request);
        $messageIdentifier = $this->getMessageIdentifier($request);
        $this->putPRToReview(
            $PRInfo,
            $workspaceIdentifier,
            $channelIdentifier,
            $messageIdentifier
        );

        return new JsonResponse($this->PRToReviewAnnouncement($request, $PRInfo));
    }

    private function getPRIdentifiers(Request $request): array
    {
        $text = $request->getContent('text');
        preg_match('#.*<https://.*/(.*)/pull/(\d+).*>.*$#', $text, $matches);

        return [$matches[1], $matches[2]];
    }

    private function PRToReviewAnnouncement(Request $request, PRInfo $PRInfo): array
    {
        $userId = $request->getContent('user_id');

        return [
            'response_type' => 'in_channel',
            'text' => [
                'type' => 'section',
                'text' => sprintf('<@%s> needs review for "%s"', $userId, $PRInfo->title),
            ],
            'accessory' => [
                'type' => 'button',
                'text' => [
                    'type' => 'plain_text',
                    'text' => 'Review',
                    'emoji' => true,
                    'style' => 'primary',
                ],
                'value' => 'show_pr',
                'url' => 'https://github.akeeo/1212',
                'action_id' => 'button_action',
            ],
        ];
    }

    private function getWorkspaceIdentifier(Request $request): string
    {
        return $request->getContent('team');
    }

    private function getChannelIdentifier(Request $request): string
    {
        $workspace = $this->getWorkspaceIdentifier($request);
        $channel = $request->getContent('channel');
        $channelName = $this->channelName($workspace, $channel);

        return ChannelIdentifierHelper::from($workspace, $channelName);
    }
    private function channelName(string $workspace, string $channel): string
    {
        return $this->getChannelInformation->fetch($workspace, $channel)->channelName;
    }

    private function getMessageIdentifier(Request $request): string
    {
        $workspace = $this->getWorkspaceIdentifier($request);
        $channel = $request->getContent('channel');
        $ts = $request->getContent('ts');

        return MessageIdentifierHelper::from($workspace, $channel, $ts);
    }

    private function PRInfo(Request $request): PRInfo
    {
        [$PRNumber, $repositoryIdentifier] = $this->getPRIdentifiers($request);
        $PRIdentifier = $this->PRIdentifier($PRNumber, $repositoryIdentifier);

        return $this->getPRInfo->fetch(PRIdentifier::fromString($PRIdentifier));
    }

    private function putPRToReview(
        PRInfo $PRInfo,
        string $channelIdentifier,
        string $workspaceIdentifier,
        string $messageIdentifier
    ): void {
        $PRToReview = new PutPRToReview();
        $PRToReview->PRIdentifier = $PRInfo->PRIdentifier;
        $PRToReview->repositoryIdentifier = $PRInfo->repositoryIdentifier;
        $PRToReview->channelIdentifier = $channelIdentifier;
        $PRToReview->workspaceIdentifier = $workspaceIdentifier;
        $PRToReview->messageIdentifier = $messageIdentifier;
        $PRToReview->authorIdentifier = $PRInfo->authorIdentifier;
        $PRToReview->title = $PRInfo->title;
        $PRToReview->GTMCount = $PRInfo->GTMCount;
        $PRToReview->notGTMCount = $PRInfo->notGTMCount;
        $PRToReview->comments = $PRInfo->comments;
        $PRToReview->CIStatus = $PRInfo->CIStatus->status;
        $PRToReview->isMerged = $PRInfo->isMerged;
        $PRToReview->isClosed = $PRInfo->isClosed;
        $PRToReview->additions = $PRInfo->additions;
        $PRToReview->deletions = $PRInfo->deletions;

        $this->logger->info(
            sprintf(
                'New PR to review - channel "%s" - repository "%s" - PR "%s".',
                $channelIdentifier,
                $PRInfo->repositoryIdentifier,
                $PRToReview->PRIdentifier
            )
        );

        $this->putPRToReviewHandler->handle($PRToReview);
    }

    private function PRIdentifier(string $PRNumber, string $repositoryIdentifier): string
    {
        return sprintf('%s/%s', $repositoryIdentifier, $PRNumber);
    }
}
