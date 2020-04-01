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

    /** @var EventHandlerRegistry */
    private $eventHandlerRegistry;

    /** @var string */
    private $secret;

    /** @var SqlDeliveredEventRepository */
    private $sqlDeliveredEventRepository;

    /** @var SqlHasEventAlreadyBeenDelivered */
    private $sqlHasEventAlreadyBeenDelivered;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        EventHandlerRegistry $eventHandlerRegistry,
        SqlHasEventAlreadyBeenDelivered $sqlHasEventAlreadyBeenDelivered,
        SqlDeliveredEventRepository $sqlDeliveredEventRepository,
        LoggerInterface $logger,
        string $secret
    ) {
        $this->eventHandlerRegistry = $eventHandlerRegistry;
        $this->secret = $secret;
        $this->sqlDeliveredEventRepository = $sqlDeliveredEventRepository;
        $this->sqlHasEventAlreadyBeenDelivered = $sqlHasEventAlreadyBeenDelivered;
        $this->logger = $logger;
    }

    public function executeAction(Request $request): Response
    {
        $this->logger->critical((string) $request->getContent());
        $this->checkSecret($request);
        $eventType = $this->eventTypeOrThrow($request);
        $event = $this->event($request);
        $this->undeliveredEventOrThrow($request);
        $this->handle($event, $eventType);

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
    private function undeliveredEventOrThrow(Request $request): void
    {
        $eventIdentifier = $request->headers->get(self::DELIVERY);
        if (null === $eventIdentifier || !is_string($eventIdentifier)) {
            throw new BadRequestHttpException('Expected delivery to have a type string');
        }

        $eventHasAlreadyBeenDelivered = $this->sqlHasEventAlreadyBeenDelivered->fetch($eventIdentifier);
        if ($eventHasAlreadyBeenDelivered) {
            throw new \RuntimeException(
                sprintf('Event has already been delivered "%s"', $eventIdentifier)
            );
        }
        $this->sqlDeliveredEventRepository->save($eventIdentifier);
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
