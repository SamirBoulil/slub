<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SlubBotAction
{
    /** @var SlubBot */
    private $slubBot;

    public function __construct(SlubBot $slubBot)
    {
        $this->slubBot = $slubBot;
    }

    public function executeAction(Request $request): Response
    {
        if ($this->isSlackChallenge($request)) {
            return $this->answerChallenge($request);
        }

        return $this->startSlubBot();
    }

    private function isSlackChallenge(Request $request): bool
    {
        $content = json_decode((string) $request->getContent(), true);

        return isset($content['challenge']);
    }

    private function answerChallenge(Request $request): JsonResponse
    {
        $content = json_decode((string) $request->getContent(), true) ?? [];
        $answer = $content['challenge'] ?? 'ANSWER NOT FOUND';

        return new JsonResponse(['challenge' => $answer]);
    }

    private function startSlubBot(): Response
    {
        $this->slubBot->start();

        return new Response();
    }
}
