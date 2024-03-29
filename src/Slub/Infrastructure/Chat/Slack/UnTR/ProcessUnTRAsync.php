<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack\UnTR;

use Slub\Application\Common\ChatClient;
use Slub\Application\UnpublishPR\UnpublishPR;
use Slub\Application\UnpublishPR\UnpublishPRHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\Chat\Common\ChatHelper;
use Slub\Infrastructure\Chat\Slack\ExplainUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Routing\RouterInterface;

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
            $PRIdentifier = ChatHelper::extractPRIdentifier($request->request->get('text'));
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

    private function confirmUnTRSuccess(Request $request): void
    {
        $message = sprintf(':ok_hand: Alright, I won\'t be sending reminders for %s', $request->request->get('text'));
        $this->chatClient->answerWithEphemeralMessage($request->request->get('response_url'), $message);
    }

}
