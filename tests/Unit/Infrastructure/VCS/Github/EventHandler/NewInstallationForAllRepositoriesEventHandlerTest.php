<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Infrastructure\VCS\Github\Client\GithubAppInstallation;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlAppInstallationRepository;
use Slub\Infrastructure\VCS\Github\Client\RefreshAccessToken;
use Slub\Infrastructure\VCS\Github\EventHandler\NewInstallationForAllRepositoriesEventHandler;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class NewInstallationForAllRepositoriesEventHandlerTest extends TestCase
{
    private const ACCESS_TOKEN_URI = '/installations/1/access_tokens';
    private const ACCESS_TOKENS_URL = 'https://api.github.com'.self::ACCESS_TOKEN_URI;
    private const ACCESS_TOKEN = 'v1.1f699f1069f60xxx';

    /** @var NewInstallationForAllRepositoriesEventHandler */
    private $newInstallationEventHandler;

    /** @var ObjectProphecy|SqlAppInstallationRepository */
    private $appInformationRepository;

    /** @var ObjectProphecy|RefreshAccessToken */
    private $refreshAccessToken;

    public function setUp(): void
    {
        $this->appInformationRepository = $this->prophesize(SqlAppInstallationRepository::class);
        $this->refreshAccessToken = $this->prophesize(RefreshAccessToken::class);
        $this->newInstallationEventHandler = new NewInstallationForAllRepositoriesEventHandler(
            $this->appInformationRepository->reveal(),
            $this->refreshAccessToken->reveal()
        );
    }

    /**
     * @test
     */
    public function it_only_listens_to_new_installation_events(): void
    {
        self::assertTrue($this->newInstallationEventHandler->supports('installation_repositories'));
        self::assertFalse($this->newInstallationEventHandler->supports('unsupported_event'));
    }

    /** @test */
    public function it_gather_app_installations_and_saves_them(): void
    {
        $installationId = 12202825;
        $repository1 = 'SamirBoulil/slub';
        $repository2 = 'akeneo/pim-community-dev';
        $newInstallationEvent = [
            'action' => 'added',
            'installation' => [
                'id' => $installationId,
                'access_tokens_url' => self::ACCESS_TOKENS_URL,
            ],
            'repositories_added' => [['full_name' => $repository1], ['full_name' => $repository2]],
        ];

        $this->refreshAccessToken->fetch((string) $installationId)->willReturn(self::ACCESS_TOKEN);

        $this->appInformationRepository->save(
            Argument::that(
                fn (GithubAppInstallation $appInstallation) => $appInstallation->installationId === (string) $installationId
                    && self::ACCESS_TOKEN === $appInstallation->accessToken
                    && $appInstallation->repositoryIdentifier === $repository1
            )
        )->shouldBeCalled();
        $this->appInformationRepository->save(
            Argument::that(
                fn (GithubAppInstallation $appInstallation) => $appInstallation->installationId === (string) $installationId
                    && self::ACCESS_TOKEN === $appInstallation->accessToken
                    && $appInstallation->repositoryIdentifier === $repository2
            )
        )->shouldBeCalled();

        $this->newInstallationEventHandler->handle($newInstallationEvent);
    }

    /** @test */
    public function it_does_not_support_actions_different_that_installation_creation(): void
    {
        $installationEventWithUnsupportedAction = ['action' => 'unsupported_action'];

        $this->expectException(\RuntimeException::class);
        $this->newInstallationEventHandler->handle($installationEventWithUnsupportedAction);
    }
}
