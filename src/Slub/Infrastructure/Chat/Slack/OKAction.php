<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 *
 * Dummy controller that returns 200 OK used to process slash commands.
 */
class OKAction
{
    public function executeAction(Request $request): Response
    {
        return new Response();
    }
}
