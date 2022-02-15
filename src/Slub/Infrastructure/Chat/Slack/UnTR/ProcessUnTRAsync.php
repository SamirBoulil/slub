<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack\UnTR;

use Slub\Application\Common\ChatClient;
use Slub\Application\UnpublishPR\UnpublishPR;
use Slub\Application\UnpublishPR\UnpublishPRHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\Chat\Slack\Common\ImpossibleToParseRepositoryURL;
use Slub\Infrastructure\Chat\Slack\ExplainUser;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ProcessUnTRAsync
{
    public function __construct(
        private UnpublishPRHandler $unpublishPRHandler,
        private ChatClient $chatClient,
        private ExplainUser $explainUser,
        private RouterInterface $router,
    ) {
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $currentRoute = $this->router->match($request->getPathInfo());
        if ('chat_slack_untr' === $currentRoute['_route']) {
            $this->processUnTR($request);
        }
    }

    private function processUnTR(Request $request): void
    {
        try {
            $PRIdentifier = $this->extractPRIdentifierFromSlackCommand($request->request->get('text'));
            $this->unTR($PRIdentifier);
            $this->confirmUnTRSuccess($request);
        } catch (\Exception|\Error $e) {
            $this->explainUser->onError($request, $e);
        }
    }

    private function unTR(PRIdentifier $PRIdentifier): void
    {
        $unpublishPR = new UnpublishPR();
        $unpublishPR->PRIdentifier = $PRIdentifier->stringValue();
        $this->unpublishPRHandler->handle($unpublishPR);
    }

    private function extractPRIdentifierFromSlackCommand(string $text): PRIdentifier
    {
        try {
            // TODO: Move this bit into GithubApiHelper.
            preg_match('#.*https://github.com/(.*)/pull/(\d+).*$#', $text, $matches);
            Assert::stringNotEmpty($matches[1]);
            Assert::stringNotEmpty($matches[2]);
            $repositoryIdentifier = $matches[1];
            $PRNumber = $matches[2];
            $PRIdentifier = GithubAPIHelper::PRIdentifierFrom($repositoryIdentifier, $PRNumber);
        } catch (\Exception) {
            throw new ImpossibleToParseRepositoryURL($text);
        }

        return $PRIdentifier;
    }

    private function confirmUnTRSuccess(Request $request): void
    {
        $message = sprintf(':ok_hand: Alright, I won\'t be sending reminders for %s', $request->request->get('text'));
        $this->chatClient->answerWithEphemeralMessage($request->request->get('response_url'), $message);
    }
}
