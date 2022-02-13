<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Repository;

use Ramsey\Uuid\Uuid;
use Slub\Infrastructure\Persistence\Sql\Repository\AppNotInstalledException;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlAppInstallationRepository;
use Slub\Infrastructure\VCS\Github\Client\GithubAppInstallation;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class SqlAppInstallationRepositoryTest extends KernelTestCase
{
    private SqlAppInstallationRepository $appInstallationRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->appInstallationRepository = $this->get('slub.infrastructure.vcs.github.client.app_installation_repository');
    }

    /** @test */
    public function it_saves_an_app_installation_and_returns_it(): void
    {
        $repositoryIdentifier = 'akeneo/pim-community-dev';
        $expected = new GithubAppInstallation();
        $expected->repositoryIdentifier = $repositoryIdentifier;
        $expected->installationId = Uuid::uuid4()->toString();
        $expected->accessToken = Uuid::uuid4()->toString();

        $this->appInstallationRepository->save($expected);
        $actual = $this->appInstallationRepository->getBy($repositoryIdentifier);

        self::assertSame($expected->repositoryIdentifier, $actual->repositoryIdentifier);
        self::assertSame($expected->installationId, $actual->installationId);
        self::assertSame($expected->accessToken, $actual->accessToken);
    }

    /** @test */
    public function it_saves_an_app_installation_without_access_token_and_returns_it(): void
    {
        $repositoryIdentifier = 'akeneo/pim-community-dev';
        $expected = new GithubAppInstallation();
        $expected->repositoryIdentifier = $repositoryIdentifier;
        $expected->installationId = Uuid::uuid4()->toString();
        $expected->accessToken = null;

        $this->appInstallationRepository->save($expected);
        $actual = $this->appInstallationRepository->getBy($repositoryIdentifier);

        self::assertSame($expected->repositoryIdentifier, $actual->repositoryIdentifier);
        self::assertSame($expected->installationId, $actual->installationId);
        self::assertNull($expected->accessToken);
    }

    /** @test */
    public function it_updates_the_app_installation(): void
    {
        $repositoryIdentifier = 'akeneo/pim-community-dev';
        $expected = new GithubAppInstallation();
        $expected->repositoryIdentifier = $repositoryIdentifier;
        $expected->installationId = Uuid::uuid4()->toString();
        $expected->accessToken = Uuid::uuid4()->toString();
        $this->appInstallationRepository->save($expected);

        $expectedInstallationId = Uuid::uuid4()->toString();
        $expectedAccessToken = Uuid::uuid4()->toString();
        $expected->installationId = $expectedInstallationId;
        $expected->accessToken = $expectedAccessToken;
        $this->appInstallationRepository->save($expected);
        $actual = $this->appInstallationRepository->getBy($repositoryIdentifier);

        self::assertSame($expected->installationId, $actual->installationId);
        self::assertSame($expected->accessToken, $actual->accessToken);
    }

    /** @test */
    public function it_throws_if_there_is_no_app_installation_for_a_repository(): void
    {
        $this->expectException(AppNotInstalledException::class);
        $this->appInstallationRepository->getBy('unknown_repository');
    }
}
