<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\EventHandler\StatusUpdatedEventHandler;
use Slub\Infrastructure\VCS\Github\Query\FindPRNumber;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class StatusUpdatedEventHandlerTest extends TestCase
{
    private const PR_NUMBER = '10';
    private const REPOSITORY_IDENTIFIER = 'SamirBoulil/slub';
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

    private const SUPPORTED_STATUS = 'travis';
    private const UNSUPPORTED_STATUS = 'UNSUPPORTED';

    private const CI_STATUS = 'A_CI_STATUS';
    private const COMMIT_REF = 'commit-ref';

    /**
     * @var StatusUpdatedEventHandler
     * @sut
     */
    private $statusUpdateEventHandler;

    /** @var ObjectProphecy|CIStatusUpdateHandler */
    private $handler;

    /** @var ObjectProphecy|GetCIStatus */
    private $getCIStatus;

    /** @var ObjectProphecy|FindPRNumber */
    private $findPRNumber;

    public function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->prophesize(CIStatusUpdateHandler::class);
        $this->getCIStatus = $this->prophesize(GetCIStatus::class);
        $this->findPRNumber = $this->prophesize(FindPRNumber::class);
        $this->statusUpdateEventHandler = new StatusUpdatedEventHandler(
            $this->handler->reveal(),
            $this->findPRNumber->reveal(),
            $this->getCIStatus->reveal(),
            implode(',', [self::SUPPORTED_STATUS, 'circle ci'])
        );
    }

    /**
     * @test
     */
    public function it_only_listens_to_status_update_review_events()
    {
        self::assertTrue($this->statusUpdateEventHandler->supports('status'));
        self::assertFalse($this->statusUpdateEventHandler->supports('unsupported_event'));
    }

    /**
     * @test
     * @dataProvider events
     */
    public function it_handles_ci_status___fetches_information_and_calls_the_handler(array $events, string $status)
    {
        $this->findPRNumber->fetch($status, self::COMMIT_REF)->willReturn(self::PR_NUMBER);
        $this->getCIStatus->fetch(
            Argument::that(
                function (PRIdentifier $PRIdentifier) {
                    return $PRIdentifier->stringValue() === self::PR_IDENTIFIER;
                }
            ),
            Argument::that(
                function (string $commitRef) {
                    return self::COMMIT_REF === $commitRef;
                }
            )
        )->willReturn(self::CI_STATUS);
        $this->handler->handle(
            Argument::that(
                function (CIStatusUpdate $command) {
                    return self::PR_IDENTIFIER === $command->PRIdentifier
                        && self::REPOSITORY_IDENTIFIER === $command->repositoryIdentifier
                        && self::CI_STATUS === $command->status;
                }
            )
        )->shouldBeCalled();

        $this->statusUpdateEventHandler->handle($events);
    }

    public function events()
    {
        return [
            'it handles supported status'       => [
                $this->supportedEvent(self::REPOSITORY_IDENTIFIER, self::PR_NUMBER),
                self::SUPPORTED_STATUS
            ],
            'it handles unsupported red status' => [
                $this->unsupportedRedEvent(self::REPOSITORY_IDENTIFIER, self::PR_NUMBER),
                self::UNSUPPORTED_STATUS,
            ],
        ];
    }

    /**
     * @test
     */
    public function it_does_not_to_handle_unsupported_and_green_check_runs()
    {
        $this->findPRNumber->fetch()->shouldNotBeCalled();
        $this->getCIStatus->fetch()->shouldNotBeCalled();
        $this->handler->handle()->shouldNotBeCalled();

        $this->statusUpdateEventHandler->handle($this->unsupportedGreenStatus());
    }

    /**
     * @test
     */
    public function it_throws_for_unsupported_conclusion()
    {
        $this->expectException(\Exception::class);
        $this->statusUpdateEventHandler->handle($this->unsupportedResult());
    }


    private function supportedEvent(string $repositoryIdentifier, string $prNumber): array
    {
        $status = self::SUPPORTED_STATUS;
        $commitRef = self::COMMIT_REF;
        $json = <<<JSON
{
  "sha": "${commitRef}",
  "name": "${status}",
  "state": "success",
  "number": ${prNumber},
  "repository": {
    "full_name": "${repositoryIdentifier}"
  }
}
JSON;

        return json_decode($json, true);
    }

    private function unsupportedRedEvent(string $repositoryIdentifier, string $prNumber): array
    {
        $status = self::UNSUPPORTED_STATUS;
        $commitRef = self::COMMIT_REF;
        $json = <<<JSON
{
  "sha": "${commitRef}",
  "name": "${status}",
  "state": "failure",
  "number": ${prNumber},
  "repository": {
    "full_name": "${repositoryIdentifier}"
  }
}
JSON;

        return json_decode($json, true);
    }

    private function unsupportedGreenStatus(): array
    {
        $json = <<<JSON
{
  "sha": "commit-ref",
  "name": "UNSUPPORTED STATUS",
  "state": "success",
  "number": 10,
  "repository": {
    "full_name": "SamirBoulil/slub"
  }
}
JSON;

        return json_decode($json, true);
    }

    private function unsupportedResult(): array
    {
        $json = <<<JSON
{
  "sha": "commit-ref",
  "name": "travis",
  "state": "UNSUPPORTED_RESULT",
  "number": 10,
  "repository": {
    "full_name": "SamirBoulil/slub"
  }
}

JSON;

        return json_decode($json, true);
    }
}
