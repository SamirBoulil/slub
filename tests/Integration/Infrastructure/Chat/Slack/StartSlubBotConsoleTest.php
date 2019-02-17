<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Drivers\Tests\ProxyDriver;
use BotMan\Studio\Testing\BotManTester;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class StartSlubBotConsoleTest extends KernelTestCase
{
    /** @var CommandTester */
    private $commandTester;

    public function setUp(): void
    {
        parent::setUp();

        $this->commandTester = $this->createCommandTester();
    }

    /**
     * @test
     */
    public function it_starts_the_slub_bot()
    {
        $slubBot = $this->get('slub.infrastructure.chat.slack.slub_bot');
        $this->assertfalse($slubBot->isStarted());
        $this->commandTester->execute(['command' => 'slub:slack:start-bot']);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertContains('Slub bot started for chat "Slack"', $this->commandTester->getDisplay());
        $this->assertTrue($slubBot->isStarted());
    }

    /**
     * @test
     */
    public function it_does_not_starts_the_slub_bot_twice()
    {
        $this->commandTester->execute(['command' => 'slub:slack:start-bot']);
        $this->commandTester->execute(['command' => 'slub:slack:start-bot']);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertContains('The command is already running in another process.', $this->commandTester->getDisplay());
    }

    private function createCommandTester(): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('slub:slack:start-bot');
        $commandTester = new CommandTester($command);

        return $commandTester;
    }
}
