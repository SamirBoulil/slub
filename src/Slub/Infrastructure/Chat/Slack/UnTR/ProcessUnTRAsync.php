<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack\UnTR;

use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Application\UnpublishPR\UnpublishPR;
use Slub\Application\UnpublishPR\UnpublishPRHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\Chat\Slack\Common\ImpossibleToParseRepositoryURL;
use Slub\Infrastructure\Persistence\Sql\Repository\AppNotInstalledException;
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
    private UnpublishPRHandler $unpublishPRHandler;
    private ChatClient $chatClient;
    private RouterInterface $router;
    private LoggerInterface $logger;

    public function __construct(
        UnpublishPRHandler $unpublishPRHandler,
        ChatClient $chatClient,
        RouterInterface $router,
        LoggerInterface $logger
    ) {
        $this->unpublishPRHandler = $unpublishPRHandler;
        $this->chatClient = $chatClient;
        $this->router = $router;
        $this->logger = $logger;
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
        } catch (ImpossibleToParseRepositoryURL $exception) {
            $this->explainPRNotParsable($request);
        } catch (AppNotInstalledException $exception) {
            $this->explainAppNotInstalled($request);
        } catch (\Exception|\Error $e) {
            $this->explainSomethingWentWrong($request);
            $this->logger->critical(sprintf('An error occurred during PR unpublish: %s', $e->getMessage()));
            $this->logger->critical($e->getTraceAsString());
        }
    }

    private function unTR(
        PRIdentifier $PRIdentifier
    ): void {
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
            [$repositoryIdentifier, $PRNumber] = ([$matches[1], $matches[2]]);
            $PRIdentifier = GithubAPIHelper::PRIdentifierFrom($repositoryIdentifier, $PRNumber);
        } catch (\Exception $e) {
            throw new ImpossibleToParseRepositoryURL($text);
        }

        return $PRIdentifier;
    }

    private function confirmUnTRSuccess(Request $request): void
    {
        $message = sprintf(':ok_hand: Alright, I won\'t be sending reminders for %s', $request->request->get('text'));
        $this->chatClient->answerWithEphemeralMessage($request->request->get('response_url'), $message);
    }

    private function explainSomethingWentWrong(Request $request): void
    {
        $responseUrl = $request->request->get('response_url');
        $this->chatClient->explainSomethingWentWrong(
            $responseUrl,
            $this->usage($request),
            'I was not able to unpublish your PR'
        );
    }

    private function explainAppNotInstalled(Request $request): void
    {
        $responseUrl = $request->request->get('response_url');
        $this->chatClient->explainAppNotInstalled(
            $responseUrl,
            sprintf('/untr %s', $request->request->get('text'))
        );
    }

    private function explainPRNotParsable(Request $request): void
    {
        $this->chatClient->explainPRURLCannotBeParsed(
            $request->request->get('response_url'),
            sprintf('/untr %s', $request->request->get('text'))
        );
    }

    private function usage(Request $request): string
    {
        return sprintf('/untr %s', $request->request->get('text'));
    }
}
