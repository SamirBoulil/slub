<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\UI\CLI;

use Prophecy\Prophecy\ObjectProphecy;
use Slub\Application\PublishReminders\PublishRemindersHandler;
use Slub\Infrastructure\UI\CLI\PublishRemindersCLI;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PublishRemindersCLITest extends KernelTestCase
{
    private const COMMAND_NAME = 'slub:send-reminders';

    private ObjectProphecy $publishRemindersHandlerMock;

    private CommandTester $commandTester;

    public function setUp(): void
    {
        parent::setUp();
        $this->publishRemindersHandlerMock = $this->prophesize(PublishRemindersHandler::class);
        $this->setUpCommand();
    }

    /**
     * @test
     */
    public function it_executes(): void
    {
        $this->publishRemindersHandlerMock->handle()->shouldBeCalled();
        $this->commandTester->execute(['command' => self::COMMAND_NAME]);
        $this->assertStringContainsString('Reminders published!', $this->commandTester->getDisplay());
    }

    private function setUpCommand(): void
    {
        $application = new Application(self::$kernel);
        /** @var PublishRemindersHandler $publishRemindersHandler */
        $publishRemindersHandler = $this->publishRemindersHandlerMock->reveal();
        $application->add(new PublishRemindersCLI($publishRemindersHandler));
        $command = $application->find(self::COMMAND_NAME);
        $this->commandTester = new CommandTester($command);
    }
}
