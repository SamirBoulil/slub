<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;
use Slub\Domain\Query\PRIsInReview;
use Slub\Infrastructure\VCS\Github\EventHandler\CheckRunEventHandler;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;
use Slub\Infrastructure\VCS\Github\Query\GetPRInfo;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CheckRunEventHandlerTest extends TestCase
{
    use ProphecyTrait;

    private const PR_NUMBER = '10';
    private const REPOSITORY_IDENTIFIER = 'SamirBoulil/slub';
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

    private const CI_STATUS = 'RED';

    /**
     * @sut
     */
    private CheckRunEventHandler $checkRunEventHandler;

    private CIStatusUpdateHandler|ObjectProphecy $handler;

    private GetPRInfoInterface|ObjectProphecy $getPRInfo;

    private PRIsInReview|ObjectProphecy $PRIsInReview;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(CIStatusUpdateHandler::class);
        $this->getPRInfo = $this->prophesize(GetPRInfo::class);
        $this->PRIsInReview = $this->prophesize(PRIsInReview::class);
        $this->checkRunEventHandler = new CheckRunEventHandler(
            $this->handler->reveal(),
            $this->getPRInfo->reveal(),
            $this->PRIsInReview->reveal()
        );
    }

    /**
     * @test
     */
    public function it_only_listens_to_check_run_events(): void
    {
        self::assertTrue($this->checkRunEventHandler->supports('check_run'));
        self::assertFalse($this->checkRunEventHandler->supports('unsupported_event'));
    }

    /**
     * @test
     * @dataProvider events
     */
    public function it_handles_check_runs_and_fetches_information_and_calls_the_handler(array $CheckRunEvent): void
    {
        $prInfo = new PRInfo();
        $prInfo->CIStatus = CheckStatus::red();
        $this->getPRInfo->fetch(
            Argument::that(
                fn (PRIdentifier $PRIdentifier) => $PRIdentifier->stringValue() === self::PR_IDENTIFIER
            )
        )->willReturn($prInfo);

        $this->handler->handle(
            Argument::that(fn (CIStatusUpdate $command) => self::PR_IDENTIFIER === $command->PRIdentifier
                && self::REPOSITORY_IDENTIFIER === $command->repositoryIdentifier
                && self::CI_STATUS === $command->status)
        )->shouldBeCalled();

        $this->checkRunEventHandler->handle($CheckRunEvent);
    }

    /**
     * @test
     */
    public function it_does_nothing_if_the_pr_is_not_in_review(): void
    {
        $prInfo = new PRInfo();
        $prInfo->CIStatus = CheckStatus::red();
        $checkRunEvent = $this->supportedEvent(self::REPOSITORY_IDENTIFIER, self::PR_NUMBER);
        $PRIdentifier = Argument::that(
            fn(PRIdentifier $PRIdentifier) => $PRIdentifier->stringValue() === self::PR_IDENTIFIER
        );

        $this->PRIsInReview->fetch($PRIdentifier)->willReturn(false);
        $this->getPRInfo->fetch()->shouldNotBeCalled();
        $this->handler->handle()->shouldNotBeCalled();

        $this->checkRunEventHandler->handle($checkRunEvent);
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
    public function it_has_a_queued_check_run_we_fetch_the_ci_status(): void
    {
        $prInfo = new PRInfo();
        $prInfo->CIStatus =  CheckStatus::red();
        $this->getPRInfo->fetch(
            Argument::that(
                fn (PRIdentifier $PRIdentifier) => $PRIdentifier->stringValue() === self::PR_IDENTIFIER
            )
        )->willReturn($prInfo);

        $this->handler->handle(
            Argument::that(fn (CIStatusUpdate $command) => self::PR_IDENTIFIER === $command->PRIdentifier
                && self::REPOSITORY_IDENTIFIER === $command->repositoryIdentifier
                && self::CI_STATUS === $command->status)
        )->shouldBeCalled();

        $this->checkRunEventHandler->handle($this->queuedCheckRun());
    }

    private function supportedEvent(string $repositoryIdentifier, string $prNumber): array
    {
        $json = <<<JSON
{
  "action": "completed",
  "check_run": {
    "status": "completed",
    "conclusion": "success",
    "name": "travis",
    "check_suite": {
      "pull_requests": [
        {
          "number": {$prNumber}
        }
      ]
    }
  },
  "repository": {
    "full_name": "{$repositoryIdentifier}"
  }
}
JSON;

        return json_decode($json, true);
    }

    private function unsupportedRedEvent(string $repositoryIdentifier, string $prNumber): array
    {
        $json = <<<JSON
{
  "action": "completed",
  "check_run": {
    "status": "completed",
    "conclusion": "failure",
    "name": "UNSUPPORTED",
    "check_suite": {
      "pull_requests": [
        {
          "number": {$prNumber}
        }
      ]
    }
  },
  "repository": {
    "full_name": "{$repositoryIdentifier}"
  }
}
JSON;

        return json_decode($json, true);
    }

    private function queuedCheckRun(): array
    {
        $json = <<<JSON
{
  "action": "completed",
  "check_run": {
    "status": "queued",
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
