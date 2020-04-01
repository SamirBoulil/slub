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
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;
use Slub\Infrastructure\VCS\Github\Query\FindPRNumber;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class StatusUpdatedEventHandlerTest extends TestCase
{
    private const PR_NUMBER = '10';
    private const REPOSITORY_IDENTIFIER = 'SamirBoulil/slub';
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

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
            $this->getCIStatus->reveal()
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
                function ($commitRef) {
                    return self::COMMIT_REF === $commitRef;
                }
            )
        )->willReturn(new CheckStatus(self::CI_STATUS, ''));
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
                'travis'
            ],
            'it handles unsupported red status' => [
                $this->unsupportedRedEvent(self::REPOSITORY_IDENTIFIER, self::PR_NUMBER),
                'UNSUPPORTED',
            ],
            'it handles unsupported pending status' => [
                $this->unsupportedPendingEvent(self::REPOSITORY_IDENTIFIER, self::PR_NUMBER),
                'UNSUPPORTED',
            ],
        ];
    }

    private function supportedEvent(string $repositoryIdentifier, string $prNumber): array
    {
        $commitRef = self::COMMIT_REF;
        $json = <<<JSON
{
  "sha": "${commitRef}",
  "name": "travis",
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
        $commitRef = self::COMMIT_REF;
        $json = <<<JSON
{
  "sha": "${commitRef}",
  "name": "UNSUPPORTED",
  "state": "failure",
  "number": ${prNumber},
  "repository": {
    "full_name": "${repositoryIdentifier}"
  }
}
JSON;

        return json_decode($json, true);
    }

    private function unsupportedPendingEvent(string $repositoryIdentifier, string $prNumber): array
    {
        $commitRef = self::COMMIT_REF;
        $json = <<<JSON
{
  "sha": "${commitRef}",
  "name": "UNSUPPORTED",
  "state": "pending",
  "number": ${prNumber},
  "repository": {
    "full_name": "${repositoryIdentifier}"
  }
}
JSON;

        return json_decode($json, true);
    }
}
