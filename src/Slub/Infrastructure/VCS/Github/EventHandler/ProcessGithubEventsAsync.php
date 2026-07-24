<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Psr\Log\LoggerInterface;
use Sentry\State\HubInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * Processes github webhook events once the response has been sent back to Github
 * (on kernel.terminate), so that webhook deliveries are acknowledged immediately
 * and are not timed out by the Github API calls the handlers perform.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class ProcessGithubEventsAsync
{
    public function __construct(
        private EventHandlerRegistry $eventHandlerRegistry,
        private HubInterface $sentryHub,
        private LoggerInterface $logger
    ) {
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        if (true !== $request->attributes->get(NewEventAction::PROCESS_EVENT_ATTRIBUTE)) {
            return;
        }

        $eventType = (string) $request->headers->get(NewEventAction::EVENT_TYPE);
        $eventPayload = json_decode((string) $request->getContent(), true);
        foreach ($this->eventHandlerRegistry->get($eventType, $eventPayload) as $eventHandler) {
            try {
                $eventHandler->handle($eventPayload);
            } catch (\Exception|\Error $e) {
                // The response has already been sent back to Github: report the failure
                // to Sentry ourselves, kernel.exception will never see it.
                $this->sentryHub->captureException($e);
                $this->logger->critical(
                    sprintf(
                        'Error while processing github event "%s" with "%s": %s',
                        $eventType,
                        $eventHandler::class,
                        $e->getMessage()
                    ),
                    ['exception' => $e]
                );
            }
        }
    }
}
