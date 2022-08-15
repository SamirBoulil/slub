<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Psr\Log\LoggerInterface;
use Slub\Infrastructure\Persistence\Sql\Query\SqlHasEventAlreadyBeenDelivered;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlDeliveredEventRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Entry point called each time a new event has been pushed from github
 *
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class NewEventAction
{
    private const SECRET_HEADER = 'X-Hub-Signature';
    private const EVENT_TYPE = 'X-GitHub-Event';
    private const DELIVERY = 'X-GitHub-Delivery';

    public function __construct(
        private EventHandlerRegistry $eventHandlerRegistry,
        private SqlHasEventAlreadyBeenDelivered $sqlHasEventAlreadyBeenDelivered,
        private SqlDeliveredEventRepository $sqlDeliveredEventRepository,
        private LoggerInterface $logger,
        private string $secret
    )
    {
    }

    public function executeAction(Request $request): Response
    {
        $this->logger->critical((string) $request->getContent());
        $this->checkSecret($request);
        if (!$this->IsEventAlreadyProcessed($request)) {
            $this->handle($request);
        }

        return new Response();
    }

    private function eventTypeOrThrow(Request $request): string
    {
        $eventType = $request->headers->get(self::EVENT_TYPE);
        if (null === $eventType || !is_string($eventType)) {
            throw new BadRequestHttpException('Expected event to have a type string');
        }

        $this->logger->critical(sprintf('Event type:%s', $eventType));

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
            $this->logger->notice(
                sprintf('Event has already been delivered "%s"', $eventIdentifier)
            );

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
        $expectedSHA1 = hash_hmac('sha1', (string) $request->getContent(), $this->secret);

        if ($expectedSHA1 !== $actualSHA1) {
            throw new BadRequestHttpException();
        }
    }

    private function handle(Request $request): void
    {
        $eventType = $this->eventTypeOrThrow($request);
        $event = $this->event($request);

        $eventHandlers = $this->eventHandlerRegistry->get($eventType);
        if (empty($eventHandlers)) {
            throw new BadRequestHttpException(sprintf('Unsupported event of type "%s"', $eventType));
        }

        $logger = $this->logger;
        array_map(
            static function (EventHandlerInterface $eventHandler) use ($event, $logger) {
                try {
                    $logger->critical('Processing logger with: '.$eventHandler::class);
                    $eventHandler->handle($event);
                } catch (\Exception $e) {
                    $logger->error($e->getMessage());
                }
            },
            $eventHandlers
        );
    }

    private function event(Request $request): array
    {
        return json_decode((string) $request->getContent(), true);
    }
}
