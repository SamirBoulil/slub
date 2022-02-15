<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Infrastructure\Chat\Slack\AppInstallation\SlackAppInstallation;
use Slub\Infrastructure\Chat\Slack\Common\BotNotInChannelException;
use Slub\Infrastructure\Chat\Slack\Common\ImpossibleToParseRepositoryURL;
use Slub\Infrastructure\Chat\Slack\Common\MessageIdentifierHelper;
use Slub\Infrastructure\Chat\Slack\ExplainUser;
use Slub\Infrastructure\Chat\Slack\Query\GetBotReactionsForMessageAndUser;
use Slub\Infrastructure\Chat\Slack\Query\GetBotUserId;
use Slub\Infrastructure\Chat\Slack\SlackClient;
use Slub\Infrastructure\Persistence\Sql\Repository\AppNotInstalledException;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ExplainUserTest extends TestCase
{
    private const URL = '/url';
    private const COMMAND = '/slash_command usage';
    private const COMMAND_TEXT = 'usage';
    private const RESPONSE_URL = '/response_url';

    private ObjectProphecy|ChatClient $chatClientSpy;
    private ExplainUser $explainUserOnError;

    public function setUp(): void
    {
        parent::setUp();
        $this->chatClientSpy = $this->prophesize(ChatClient::class);

        $this->explainUserOnError = new ExplainUser(
            $this->chatClientSpy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );
    }

    public function test_it_explains_when_the_pr_url_cannot_be_parsed(): void
    {
        $impossibleToParseRepositoryURLEvent = $this->eventWithException(new ImpossibleToParseRepositoryURL());
        $this->explainUserOnError->onError(
            $impossibleToParseRepositoryURLEvent->getRequest(),
            $impossibleToParseRepositoryURLEvent->getThrowable()
        );
        $this->chatClientSpy->explainPRURLCannotBeParsed(self::RESPONSE_URL, $this->usage())->shouldBeCalled();
    }

    public function test_it_explains_when_the_slack_app_is_not_installed(): void
    {
        $AppNotInstalledEvent = $this->eventWithException(new AppNotInstalledException());
        $this->explainUserOnError->onError($AppNotInstalledEvent->getRequest(), $AppNotInstalledEvent->getThrowable());
        $this->chatClientSpy->explainAppNotInstalled(self::RESPONSE_URL, $this->usage())->shouldBeCalled();
    }

    public function test_it_explains_when_the_bot_is_not_in_the_channel(): void
    {
        $BotNotInChannelEvent = $this->eventWithException(new BotNotInChannelException());
        $this->explainUserOnError->onError($BotNotInChannelEvent->getRequest(), $BotNotInChannelEvent->getThrowable());
        $this->chatClientSpy->explainBotNotInChannel(self::RESPONSE_URL, $this->usage())->shouldBeCalled();
    }

    public function test_it_explains_when_something_went_wrong(): void
    {
        $somethingWentWrongEvent = $this->eventWithException(new \Exception());
        $this->explainUserOnError->onError(
            $somethingWentWrongEvent->getRequest(),
            $somethingWentWrongEvent->getThrowable()
        );
        $this->chatClientSpy->explainSomethingWentWrong(self::RESPONSE_URL, $this->usage())->shouldBeCalled();
    }

    private function usage(): string
    {
        return sprintf('%s %s', self::COMMAND, self::COMMAND_TEXT);
    }

    private function eventWithException(\Exception $e): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->prophesize(KernelInterface::class)->reveal(),
            Request::create(
                self::URL,
                'POST',
                [
                    'command' => self::COMMAND,
                    'text' => self::COMMAND_TEXT,
                    'response_url' => self::RESPONSE_URL,
                ]
            ),
            0,
            $e
        );
    }
}
