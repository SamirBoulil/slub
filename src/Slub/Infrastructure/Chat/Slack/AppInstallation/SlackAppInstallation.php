<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack\AppInstallation;

use ConvenientImmutability\Immutable;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class SlackAppInstallation
{
    use Immutable;

    public string $workspaceId;

    public string $accessToken;
}
