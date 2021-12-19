<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack\TR;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class OKAction
{
    public function executeAction(Request $request): Response
    {
        return new Response();
    }
}
