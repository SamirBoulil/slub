<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack\TR;

use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Application\PutPRToReview\PutPRToReview;
use Slub\Application\PutPRToReview\PutPRToReviewHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;
use Slub\Infrastructure\Chat\Common\ChatHelper;
use Slub\Infrastructure\Chat\Slack\Common\ChannelIdentifierHelper;
use Slub\Infrastructure\Chat\Slack\Common\ImpossibleToParseRepositoryURL;
use Slub\Infrastructure\Chat\Slack\ExplainUser;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class ProcessTRAsync
{
    public function __construct(
        private PutPRToReviewHandler $putPRToReviewHandler,
        private GetPRInfoInterface $getPRInfo,
        private ChatClient $chatClient,
        private ExplainUser $explainUser,
        private RouterInterface $router,
        private LoggerInterface $logger
    ) {
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
            $PRIdentifier = ChatHelper::extractPRIdentifier($request->request->get('text'));
            $this->putPRToReview($PRIdentifier, $request);
        } catch (\Exception|\Error $e) {
            $this->explainUser->onError($request, $e);
        }
    }

    private function getChannelIdentifier(Request $request): string
    {
        $workspace = $request->request->get('team_id');
        $channelName = $request->request->get('channel_id');

        return ChannelIdentifierHelper::from($workspace, $channelName);
    }

    private function publishToReviewAnnouncement(
        PRInfo $PRInfo,
        string $channelIdentifier,
        string $authorIdentifier
    ): string {
        $PRUrl = GithubAPIHelper::PRUrl(PRIdentifier::fromString($PRInfo->PRIdentifier));

        return $this->chatClient->publishToReviewMessage(
            $channelIdentifier,
            $PRUrl,
            $PRInfo->title,
            $PRInfo->repositoryIdentifier,
            $PRInfo->additions,
            $PRInfo->deletions,
            $authorIdentifier,
            $PRInfo->authorImageUrl,
            $PRInfo->description,
        );
    }

    private function putPRToReview(
        PRIdentifier $PRIdentifier,
        Request $request
    ): void {
        $PRInfo = $this->getPRInfo->fetch($PRIdentifier);
        $workspaceIdentifier = $request->request->get('team_id');
        $channelIdentifier = $this->getChannelIdentifier($request);

        // TODO: Should be done when PRPutToReview event has been sent
        $authorIdentifier = $request->request->get('user_id');
        $messageIdentifier = $this->publishToReviewAnnouncement($PRInfo, $channelIdentifier, $authorIdentifier);

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

//        $this->logger->debug(
//            sprintf(
//                'New PR to review - workspace "%s" - channel "%s" - repository "%s" - author "%s" - message "%s" - PR "%s".',
//                $PRToReview->workspaceIdentifier,
//                $PRToReview->channelIdentifier,
//                $PRToReview->repositoryIdentifier,
//                $PRToReview->authorIdentifier,
//                $PRToReview->messageIdentifier,
//                $PRToReview->PRIdentifier
//            )
//        );

        $this->putPRToReviewHandler->handle($PRToReview);
    }
}
