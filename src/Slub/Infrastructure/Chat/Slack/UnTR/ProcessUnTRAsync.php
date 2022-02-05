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
            $this->explainAuthorURLCannotBeParsed($request);
        } catch (AppNotInstalledException $exception) {
            $this->explainAuthorAppIsNotInstalled($request);
        } catch (\Exception|\Error $e) {
            $this->explainAuthorPRCouldNotBeSubmittedToReview($request);
            $this->logger->error(sprintf('An error occurred during a TR submission: %s', $e->getMessage()));
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

    // TODO: Move in SlackClient put in command with ProcessTRAsync
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

    // TODO: Move in SlackClient put in command with ProcessTRAsync
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

    // TODO: Move in SlackClient put in command with ProcessTRAsync
    private function explainAuthorAppIsNotInstalled(Request $request): void
    {
        $authorInput = $request->request->get('text');
        $responseUrl = $request->request->get('response_url');
        $text = <<<SLACK
:warning: `/tr %s`
:thinking_face: It looks like Yeee is not installed on this repository but you <https://github.com/apps/slub-yeee|Install it> now!
SLACK;
        $this->chatClient->answerWithEphemeralMessage($responseUrl, sprintf($text, $authorInput));
    }

    private function confirmUnTRSuccess(Request $request): void
    {
        $message = sprintf('Alright, I won\'t be sending reminders for %s', $request->request->get('text'));
        $this->chatClient->answerWithEphemeralMessage($request->request->get('response_url'), $message);
    }
}
