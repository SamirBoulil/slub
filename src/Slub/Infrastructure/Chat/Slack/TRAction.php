<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class TRAction
{
    public function executeAction(Request $request): Response
    {
        return new Response();
    }
}
