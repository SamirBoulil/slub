<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Repository;

use Ramsey\Uuid\Uuid;
use Slub\Infrastructure\Chat\Slack\AppInstallation\SlackAppInstallation;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class SqlSlackAppInstallationRepositoryTest extends KernelTestCase
{
    private SqlSlackAppInstallationRepository $slackAppInstallationRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->slackAppInstallationRepository = $this->get('slub.infrastructure.chat.slack.slack_app_installation_repository');
    }

    /** @test */
    public function it_saves_an_app_installation_and_returns_it(): void
    {
        $workspaceId = Uuid::uuid4()->toString();
        $expected = new SlackAppInstallation();
        $expected->workspaceId = $workspaceId;
        $expected->accessToken = Uuid::uuid4()->toString();

        $this->slackAppInstallationRepository->save($expected);
        $actual = $this->slackAppInstallationRepository->getBy($workspaceId);

        self::assertSame($expected->workspaceId, $actual->workspaceId);
        self::assertSame($expected->accessToken, $actual->accessToken);
    }

    /** @test */
    public function it_updates_the_app_installation(): void
    {
        $workspaceId = Uuid::uuid4()->toString();

        $initialSlackAppInstallation = new SlackAppInstallation();
        $initialSlackAppInstallation->workspaceId = $workspaceId;
        $initialSlackAppInstallation->accessToken = Uuid::uuid4()->toString();
        $this->slackAppInstallationRepository->save($initialSlackAppInstallation);

        $expected = new SlackAppInstallation();
        $expected->workspaceId = $workspaceId;
        $expected->accessToken = Uuid::uuid4()->toString();
        $this->slackAppInstallationRepository->save($expected);

        $actual = $this->slackAppInstallationRepository->getBy($workspaceId);

        self::assertSame($expected->workspaceId, $actual->workspaceId);
        self::assertSame($expected->accessToken, $actual->accessToken);
    }

    /** @test */
    public function it_throws_if_there_is_no_app_installation_for_a_repository(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->slackAppInstallationRepository->getBy('unknown_workspace_id');
    }
}
