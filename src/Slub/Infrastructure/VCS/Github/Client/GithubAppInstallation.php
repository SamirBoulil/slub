<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Client;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class GithubAppInstallation
{
    public string $repositoryIdentifier;

    public string $installationId;

    public ?string $accessToken = null;
}
