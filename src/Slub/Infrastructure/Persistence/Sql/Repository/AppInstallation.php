<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Repository;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class AppInstallation
{
    /** @var string */
    public $repositoryIdentifier;

    /** @var string */
    public $installationId;

    /** @var string */
    public $accessToken;
}
