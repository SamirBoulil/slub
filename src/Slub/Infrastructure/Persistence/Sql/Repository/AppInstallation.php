<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Repository;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class AppInstallation
{
    public string $repositoryIdentifier;

    public string $installationId;

    public string $accessToken;
}
