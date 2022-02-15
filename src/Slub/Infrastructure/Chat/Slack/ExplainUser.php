<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use JetBrains\PhpStorm\Pure;
use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Infrastructure\Chat\Slack\Common\BotNotInChannelException;
use Slub\Infrastructure\Chat\Slack\Common\ImpossibleToParseRepositoryURL;
use Slub\Infrastructure\Persistence\Sql\Repository\AppNotInstalledException;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ExplainUser
{
    public function __construct(
        private ChatClient $chatClient,
        private LoggerInterface $logger
    ) {
    }

    #[Pure]
    public function onError(Request $request, \Exception|\Error $exception): void
    {
        match (get_class($exception)) {
            ImpossibleToParseRepositoryURL::class => $this->explainPRNotParsable($request),
            AppNotInstalledException::class => $this->explainAppNotInstalled($request),
            BotNotInChannelException::class => $this->explainBotInChannel($request),
            \Exception::class, \Error::class => $this->explainSomethingWentWrong($request, $exception)
        };
    }

    private function explainAppNotInstalled(Request $request): void
    {
        $this->chatClient->explainAppNotInstalled($request->request->get('response_url'), $this->usage($request));
    }

    private function explainPRNotParsable(Request $request): void
    {
        $this->chatClient->explainPRURLCannotBeParsed($request->request->get('response_url'), $this->usage($request));
    }

    private function explainBotInChannel(Request $request): void
    {
        $responseUrl = $request->request->get('response_url');
        $this->chatClient->explainBotNotInChannel($responseUrl, $this->usage($request));
    }

    private function explainSomethingWentWrong(Request $request, \Exception $e): void
    {
        $this->logger->critical('Something went wrong:');
        $this->logger->critical($e->getTraceAsString());
        $responseUrl = $request->request->get('response_url');
        $this->chatClient->explainSomethingWentWrong($responseUrl, $this->usage($request));

    }

    private function usage(Request $request): string
    {
        return sprintf('%s %s', $request->request->get('command'), $request->request->get('text'));
    }
}
