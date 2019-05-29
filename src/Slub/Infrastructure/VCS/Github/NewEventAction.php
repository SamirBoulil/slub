<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github;

use Slub\Infrastructure\VCS\Github\EventHandler\EventHandlerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Entry point called each time a new event has been pushed from github
 *
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class NewEventAction
{
    private const SECRET_HEADER = 'X-Hub-Signature';
    private const EVENT_TYPE = 'X-GitHub-Event';

    /** @var EventHandlerRegistry */
    private $eventHandlerRegistry;

    /** @var string */
    private $secret;

    public function __construct(EventHandlerRegistry $eventHandlerRegistry, string $secret)
    {
        $this->eventHandlerRegistry = $eventHandlerRegistry;
        $this->secret = $secret;
    }

    public function executeAction(Request $request): Response
    {
        $this->checkSecret($request);
        $eventType = $this->eventTypeOrThrow($request);
        $event = $this->event($request);
        $this->handle($event, $eventType);

        return new Response();
    }

    private function eventTypeOrThrow(Request $request): string
    {
        $eventType = $request->headers->get(self::EVENT_TYPE);
        if (null === $eventType || !is_string($eventType)) {
            throw new BadRequestHttpException('Expected event to have a type string');
        }

        return $eventType;
    }

    private function checkSecret(Request $request): void
    {
        $secretHeader = $request->headers->get(self::SECRET_HEADER);
        if (null === $secretHeader || empty($secretHeader) || !is_string($secretHeader)) {
            throw new BadRequestHttpException();
        }
        $actualSHA1 = last(explode('=', $secretHeader));
        $expectedSHA1 = hash_hmac('sha1', (string) $request->getContent(), $this->secret);

        if ($expectedSHA1 !== $actualSHA1) {
            throw new BadRequestHttpException();
        }
    }

    private function handle(array $event, string $eventType): void
    {
        $eventHandler = $this->eventHandlerRegistry->get($eventType);
        if (null === $eventHandler) {
            throw new BadRequestHttpException(sprintf('Unsupported event of type "%s"', $eventType));
        }
        $eventHandler->handle($event);
    }

    private function event(Request $request): array
    {
        return json_decode((string) $request->getContent(), true);
    }
}
