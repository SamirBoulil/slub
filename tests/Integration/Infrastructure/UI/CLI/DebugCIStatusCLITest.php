<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\UI\CLI;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\PRInfo;
use Slub\Infrastructure\UI\CLI\DebugCIStatusCLI;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CIStatus;
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
    public const PR_IDENTIFIER = 'samirboulil/slub/123';

    private CommandTester $commandTester;

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
    public function it_executes(): void
    {
        $prInfo = $this->aPRInfo();
        $prInfo->CIStatus = CIStatus::green();

        $this->getPRInfo->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER))->willReturn($prInfo);
        $this->commandTester->execute(['command' => self::COMMAND_NAME, 'pull_request_link' => 'https://github.com/samirboulil/slub/pull/123']);

        $this->assertStringContainsString('GREEN', $this->commandTester->getDisplay());
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

    protected function aPRInfo(): PRInfo
    {
        $prInfo = new PRInfo();
        $prInfo->PRIdentifier = self::PR_IDENTIFIER;
        $prInfo->authorIdentifier = 'dummy';
        $prInfo->title = 'dummy';
        $prInfo->GTMCount = 1;
        $prInfo->notGTMCount = 1;
        $prInfo->comments = 1;
        $prInfo->CIStatus = CIStatus::green();
        $prInfo->isMerged = false;
        $prInfo->isClosed = false;

        return $prInfo;
    }
}
