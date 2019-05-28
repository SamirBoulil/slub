<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;
use Slub\Infrastructure\VCS\Github\EventHandler\CheckRunEventHandler;
use Slub\Infrastructure\VCS\Github\Query\GetPRInfo;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class CheckRunEventHandlerTest extends TestCase
{
    private const PR_NUMBER = '10';
    private const REPOSITORY_IDENTIFIER = 'SamirBoulil/slub';
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

    private const SUPPORTED_CHECK_RUN = 'travis';
    private const UNSUPPORTED_CHECK_RUN = 'UNSUPPORTED';

    private const CI_STATUS = 'A_CI_STATUS';

    /**
     * @var CheckRunEventHandler
     * @sut
     */
    private $checkRunEventHandler;

    /** @var ObjectProphecy|CIStatusUpdateHandler */
    private $handler;

    /** @var ObjectProphecy|GetPRInfoInterface */
    private $getPRInfo;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(CIStatusUpdateHandler::class);
        $this->getPRInfo = $this->prophesize(GetPRInfo::class);
        $this->checkRunEventHandler = new CheckRunEventHandler(
            $this->handler->reveal(),
            $this->getPRInfo->reveal(),
            self::SUPPORTED_CHECK_RUN . ',circle ci'
        );
    }

    /**
     * @test
     */
    public function it_only_listens_to_check_run_events()
    {
        self::assertTrue($this->checkRunEventHandler->supports('check_run'));
        self::assertFalse($this->checkRunEventHandler->supports('unsupported_event'));
    }

    /**
     * @test
     * @dataProvider events
     */
    public function it_handles_check_runs_fetches_information_and_calls_the_handler(array $events)
    {
        $prInfo = new PRInfo();
        $prInfo->CIStatus = self::CI_STATUS;
        $this->getPRInfo->fetch(
            Argument::that(
                function (PRIdentifier $PRIdentifier) {
                    return $PRIdentifier->stringValue() === self::PR_IDENTIFIER;
                }
            )
        )->willReturn($prInfo);

        $this->handler->handle(
            Argument::that(function (CIStatusUpdate $command) {
                return self::PR_IDENTIFIER === $command->PRIdentifier
                    && self::REPOSITORY_IDENTIFIER === $command->repositoryIdentifier
                    && self::CI_STATUS === $command->status;
            })
        )->shouldBeCalled();

        $this->checkRunEventHandler->handle($events);
    }

    public function events(): array
    {
        return [
            'it handles supported check run' => [
                $this->supportedEvent(self::REPOSITORY_IDENTIFIER, self::PR_NUMBER),
            ],
            'it handles unsupported red check runs' => [
                $this->unsupportedRedEvent(self::REPOSITORY_IDENTIFIER, self::PR_NUMBER),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_does_not_to_handle_unsupported_and_green_check_runs()
    {
        $this->getPRInfo->fetch()->shouldNotBeCalled();
        $this->handler->handle()->shouldNotBeCalled();

        $this->checkRunEventHandler->handle($this->unsupportedGreenCI());
    }

    /**
     * @test
     */
    public function it_throws_for_unsupported_conclusion()
    {
        $this->expectException(\Exception::class);
        $this->checkRunEventHandler->handle($this->unsupportedResult());
    }

    private function supportedEvent(string $repositoryIdentifier, string $prNumber): array
    {
        $checkRunName = self::SUPPORTED_CHECK_RUN;
        $json = <<<JSON
{
  "action": "completed",
  "check_run": {
    "status": "completed",
    "conclusion": "success",
    "name": "${checkRunName}",
    "check_suite": {
      "pull_requests": [
        {
          "number": ${prNumber}
        }
      ]
    }
  },
  "repository": {
    "full_name": "${repositoryIdentifier}"
  }
}
JSON;

        return json_decode($json, true);
    }

    private function unsupportedRedEvent(string $repositoryIdentifier, string $prNumber): array
    {
        $checkRunName = self::UNSUPPORTED_CHECK_RUN;
        $json = <<<JSON
{
  "action": "completed",
  "check_run": {
    "status": "completed",
    "conclusion": "failure",
    "name": "${checkRunName}",
    "check_suite": {
      "pull_requests": [
        {
          "number": ${prNumber}
        }
      ]
    }
  },
  "repository": {
    "full_name": "${repositoryIdentifier}"
  }
}
JSON;

        return json_decode($json, true);
    }

    private function unsupportedGreenCI(): array
    {
        $json = <<<JSON
{
  "action": "completed",
  "check_run": {
    "status": "completed",
    "conclusion": "success",
    "name": "UNSUPPORTED_CHECK_RUN"
  }
}
JSON;

        return json_decode($json, true);
    }

    private function unsupportedResult(): array
    {
        $json = <<<JSON
{
  "action": "completed",
  "check_run": {
    "status": "completed",
    "conclusion": "WRONG_CONCLUSION",
    "name": "travis",
    "check_suite": {
      "pull_requests": [
        {
          "number": 10
        }
      ]
    },
    "pull_requests": [
      {
        "url": "https://api.github.com/repos/SamirBoulil/slub/pulls/26",
        "number": 26
      }
    ]
  },
  "repository": {
    "full_name": "SamirBoulil/slub"
  }
}
JSON;

        return json_decode($json, true);
    }
}
