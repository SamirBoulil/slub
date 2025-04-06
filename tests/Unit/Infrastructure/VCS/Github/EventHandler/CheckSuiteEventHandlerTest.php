<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\IsPRInReview;
use Slub\Domain\Query\PRInfo;
use Slub\Infrastructure\Persistence\Sql\Repository\VCSEventRecorder;
use Slub\Infrastructure\VCS\Github\EventHandler\CheckSuiteEventHandler;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CIStatus;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;
use Slub\Infrastructure\VCS\Github\Query\GetPRInfo;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CheckSuiteEventHandlerTest extends TestCase
{
    use ProphecyTrait;

    private const PR_NUMBER = '10';
    private const REPOSITORY_IDENTIFIER = 'SamirBoulil/slub';
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';
    private const CI_STATUS = 'RED';
    private const HEAD_SHA = 'head_sha_b0123FAZE';

    /**
     * @sut
     */
    private CheckSuiteEventHandler $checkSuiteEventHandler;

    private CIStatusUpdateHandler|ObjectProphecy $handler;

    private GetCIStatus|ObjectProphecy $getCIStatus;

    private IsPRInReview|ObjectProphecy $IsPRInReview;
    private ObjectProphecy|VCSEventRecorder $eventRecorder;
    private LoggerInterface|ObjectProphecy $logger;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(CIStatusUpdateHandler::class);
        $this->IsPRInReview = $this->prophesize(IsPRInReview::class);
        $this->getCIStatus = $this->prophesize(GetCIStatus::class);
        $this->eventRecorder = $this->prophesize(VCSEventRecorder::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->checkSuiteEventHandler = new CheckSuiteEventHandler(
            $this->handler->reveal(),
            $this->getCIStatus->reveal(),
            $this->IsPRInReview->reveal(),
            $this->eventRecorder->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @test
     */
    public function it_only_listens_to_check_suite_events(): void
    {
        self::assertTrue($this->checkSuiteEventHandler->supports('check_suite', []));
        self::assertFalse($this->checkSuiteEventHandler->supports('unsupported_event', []));
    }

    /**
     * @test
     * @dataProvider events
     */
    public function it_handles_check_suites_and_fetches_information_and_calls_the_handler(array $checkSuiteEvent): void
    {
        $CIStatus = CIStatus::red();
        $PRIdentifier = Argument::that(
            fn(PRIdentifier $PRIdentifier) => $PRIdentifier->stringValue() === self::PR_IDENTIFIER
        );

        $this->IsPRInReview->fetch($PRIdentifier)->willReturn(true);
        $this->getCIStatus->fetch($PRIdentifier, self::HEAD_SHA)->willReturn($CIStatus);
        $this->handler->handle(
            Argument::that(fn(CIStatusUpdate $command) => self::PR_IDENTIFIER === $command->PRIdentifier
                && self::REPOSITORY_IDENTIFIER === $command->repositoryIdentifier
                && self::CI_STATUS === $command->status)
        )->shouldBeCalled();
        $this->eventRecorder->recordEvent('SamirBoulil/slub', 'travis', 'check_suite')->shouldBeCalled();

        $this->checkSuiteEventHandler->handle($checkSuiteEvent);
    }

    /**
     * @test
     */
    public function it_does_nothing_if_the_pr_is_not_in_review(): void
    {
        $checkSuiteEvent = $this->supportedEvent(self::REPOSITORY_IDENTIFIER, self::PR_NUMBER);
        $PRIdentifier = Argument::that(
            fn(PRIdentifier $PRIdentifier) => $PRIdentifier->stringValue() === self::PR_IDENTIFIER
        );

        $this->IsPRInReview->fetch($PRIdentifier)->willReturn(false);
        $this->getCIStatus->fetch()->shouldNotBeCalled();
        $this->handler->handle()->shouldNotBeCalled();
        $this->eventRecorder->recordEvent()->shouldNotBeCalled();

        $this->checkSuiteEventHandler->handle($checkSuiteEvent);
    }

    public function events(): array
    {
        return [
            'it handles supported check suite' => [
                $this->supportedEvent(self::REPOSITORY_IDENTIFIER, self::PR_NUMBER),
            ],
            'it handles unsupported red check suites' => [
                $this->unsupportedRedEvent(self::REPOSITORY_IDENTIFIER, self::PR_NUMBER),
            ],
        ];
    }

    private function supportedEvent(string $repositoryIdentifier, string $prNumber): array
    {
        $headSha = self::HEAD_SHA;
        $json = <<<JSON
{
  "action": "neutral",
  "check_suite": {
    "conclusion": "neutral",
    "head_sha": "{$headSha}",
    "pull_requests": [{"number": {$prNumber}}],
    "app": { "name": "travis" } },
  "repository": {
    "full_name": "{$repositoryIdentifier}"
  }
}
JSON;

        return json_decode($json, true);
    }

    private function unsupportedRedEvent(string $repositoryIdentifier, string $prNumber): array
    {
        $headSha = self::HEAD_SHA;
        $json = <<<JSON
{
  "action": "completed",
  "check_suite": {
    "conclusion": "UNSUPPORTED",
    "pull_requests": [{"number": {$prNumber}}],
    "head_sha": "{$headSha}",
    "app": { "name": "travis" }
  },
  "repository": {
    "full_name": "{$repositoryIdentifier}"
  }
}
JSON;

        return json_decode($json, true);
    }

    private function queuedChecksuite(): array
    {
        $json = <<<JSON
{
  "action": "completed",
  "check_suite": {
    "status": "queued",
    "conclusion": "WRONG_CONCLUSION",
    "app": { "name": "travis" },
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
