<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Application\ChangePRSize\ChangePRSize;
use Slub\Application\ChangePRSize\ChangePRSizeHandler;

/**
 * @author    Pierrick Martos <pierrick.martos@gmail.com>
 */
class PRSizeChangedEventHandler implements EventHandlerInterface
{
    private const PULL_REQUEST_EVENT_TYPE = 'pull_request';

    public function __construct(private ChangePRSizeHandler $largePRHandler)
    {
    }

    public function supports(string $eventType): bool
    {
        return self::PULL_REQUEST_EVENT_TYPE === $eventType;
    }

    public function handle(array $PRLargeEvent): void
    {
        if ($this->isPullRequestEventSupported($PRLargeEvent)) {
            $this->updatePR($PRLargeEvent);
        }
    }

    private function isPullRequestEventSupported(array $PRLargeEvent): bool
    {
        return isset(
            $PRLargeEvent['pull_request']['additions'],
            $PRLargeEvent['pull_request']['deletions'],
            $PRLargeEvent['repository']['full_name'],
            $PRLargeEvent['pull_request']['number']
        );
    }

    private function updatePR(array $PRLargeEvent): void
    {
        $command = new ChangePRSize();
        $command->PRIdentifier = $this->getPRIdentifier($PRLargeEvent);
        $command->additions = $PRLargeEvent['pull_request']['additions'];
        $command->deletions = $PRLargeEvent['pull_request']['deletions'];
        $this->largePRHandler->handle($command);
    }

    private function getPRIdentifier(array $PRLargeEvent): string
    {
        return sprintf(
            '%s/%s',
            $PRLargeEvent['repository']['full_name'],
            $PRLargeEvent['pull_request']['number']
        );
    }
}
