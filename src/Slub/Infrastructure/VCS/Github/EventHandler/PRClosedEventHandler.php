<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Application\ClosePR\ClosePR;
use Slub\Application\ClosePR\ClosePRHandler;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PRClosedEventHandler implements EventHandlerInterface
{
    private const PULL_REQUEST_EVENT_TYPE = 'pull_request';
    public const PR_CLOSED_ACTION = 'closed';

    public function __construct(private ClosePRHandler $closePRHandler)
    {
    }

    public function supports(string $eventType, array $eventPayload): bool
    {
        return self::PULL_REQUEST_EVENT_TYPE === $eventType;
    }

    public function handle(array $PRSynchronizedEvent): void
    {
        if ($this->isPRClosed($PRSynchronizedEvent)) {
            $this->closePR($PRSynchronizedEvent);
        }
    }

    private function isPRClosed(array $PRClosedEvent): bool
    {
        return isset($PRClosedEvent['pull_request']['merged'], $PRClosedEvent['action']) && self::PR_CLOSED_ACTION === $PRClosedEvent['action'];
    }

    private function closePR(array $PRClosedEvent): void
    {
        $command = new ClosePR();
        $command->PRIdentifier = $this->getPRIdentifier($PRClosedEvent);
        $command->repositoryIdentifier = $PRClosedEvent['repository']['full_name'];
        $command->isMerged = $PRClosedEvent['pull_request']['merged'];
        $this->closePRHandler->handle($command);
    }

    private function getPRIdentifier(array $CIStatusUpdate): string
    {
        return sprintf(
            '%s/%s',
            $CIStatusUpdate['repository']['full_name'],
            $CIStatusUpdate['pull_request']['number']
        );
    }
}
