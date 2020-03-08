<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\UI\CLI;

use Slub\Application\PublishReminders\PublishRemindersHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\PRInfo;
use Slub\Infrastructure\UI\CLI\DebugCIStatusCLI;
use Slub\Infrastructure\UI\CLI\PublishRemindersCLI;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;
use Slub\Infrastructure\VCS\Github\Query\GetPRInfo;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class DebugCIStatusCLITest extends KernelTestCase
{
    private const COMMAND_NAME = 'slub:debug:ci-status';

    /** @var CommandTester */
    private $commandTester;

    /** * @var \Prophecy\Prophecy\ObjectProphecy|GetPRInfo */
    private $getPRInfo;

    public function setUp(): void
    {
        parent::setUp();
        $this->getPRInfo = $this->prophesize(GetPRInfo::class);
        $this->setUpCommand();
    }

    /**
     * @test
     */
    public function it_executes()
    {
        $ciStatus = 'GREEN';
        $prInfo = new PRInfo();
        $prInfo->CIStatus = new CheckStatus($ciStatus);

        $this->getPRInfo->fetch(PRIdentifier::fromString('samirboulil/slub/123'))->willReturn($prInfo);
        $this->commandTester->execute(['command' => self::COMMAND_NAME, 'pull_request_link' => 'https://github.com/samirboulil/slub/pull/123']);

        $this->assertContains($ciStatus, $this->commandTester->getDisplay());
    }

    private function setUpCommand(): void
    {
        $application = new Application(self::$kernel);
        /** @var GetPRInfo $getPRInfo */
        $getPRInfo = $this->getPRInfo->reveal();
        $application->add(new DebugCIStatusCLI($getPRInfo));
        $command = $application->find(self::COMMAND_NAME);
        $this->commandTester = new CommandTester($command);
    }
}
