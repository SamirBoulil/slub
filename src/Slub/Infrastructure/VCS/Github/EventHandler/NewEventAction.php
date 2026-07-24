<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Infrastructure\Persistence\Sql\Query\SqlHasEventAlreadyBeenDelivered;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlDeliveredEventRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Entry point called each time a new event has been pushed from github.
 *
 * It only validates and acknowledges the event: the actual processing happens after
 * the response has been sent back to Github (see ProcessGithubEventsAsync), so that
 * webhook deliveries are not timed out by the Github API calls the handlers perform.
 *
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class NewEventAction
{
    public const PROCESS_EVENT_ATTRIBUTE = 'github_event_to_process';
    public const EVENT_TYPE = 'X-GitHub-Event';

    private const SECRET_HEADER = 'X-Hub-Signature';
    private const DELIVERY = 'X-GitHub-Delivery';

    public function __construct(
        private EventHandlerRegistry $eventHandlerRegistry,
        private SqlHasEventAlreadyBeenDelivered $sqlHasEventAlreadyBeenDelivered,
        private SqlDeliveredEventRepository $sqlDeliveredEventRepository,
        private string $secret
    ) {
    }

    public function executeAction(Request $request): Response
    {
        $this->checkSecret($request);

        $eventType = $this->eventTypeOrThrow($request);
        $eventPayload = $this->eventPayload($request);
        $eventHandlers = $this->eventHandlerRegistry->get($eventType, $eventPayload);

        if (!empty($eventHandlers) && !$this->IsEventAlreadyProcessed($request)) {
            $request->attributes->set(self::PROCESS_EVENT_ATTRIBUTE, true);
        }

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

    /**
     * @throws \RuntimeException
     */
    private function IsEventAlreadyProcessed(Request $request): bool
    {
        $eventIdentifier = $request->headers->get(self::DELIVERY);
        if (!is_string($eventIdentifier)) {
            throw new BadRequestHttpException('Expected delivery to have a type string');
        }

        $isEventAlreadyProcessed = false;
        $eventHasAlreadyBeenDelivered = $this->sqlHasEventAlreadyBeenDelivered->fetch($eventIdentifier);
        if ($eventHasAlreadyBeenDelivered) {
            $isEventAlreadyProcessed = true;
        }
        $this->sqlDeliveredEventRepository->save($eventIdentifier);

        return $isEventAlreadyProcessed;
    }

    private function checkSecret(Request $request): void
    {
        $secretHeader = $request->headers->get(self::SECRET_HEADER);
        if (empty($secretHeader) || !is_string($secretHeader)) {
            throw new BadRequestHttpException();
        }
        $headerValue = explode('=', $secretHeader);
        $actualSHA1 = end($headerValue);
        $expectedSHA1 = hash_hmac('sha1', (string)$request->getContent(), $this->secret);

        if ($expectedSHA1 !== $actualSHA1) {
            throw new BadRequestHttpException();
        }
    }

    private function eventPayload(Request $request): array
    {
        return json_decode((string)$request->getContent(), true);
    }
}
