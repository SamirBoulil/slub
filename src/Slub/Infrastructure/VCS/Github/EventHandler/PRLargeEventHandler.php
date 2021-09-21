<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Application\CIStatusUpdate\WarnLargePR;
use Slub\Application\WarnLargePR\WarnLargePRHandler;

/**
 * @author    Pierrick Martos <pierrick.martos@gmail.com>
 */
class PRLargeEventHandler implements EventHandlerInterface
{
    private const PULL_REQUEST_EVENT_TYPE = 'pull_request';

    private WarnLargePRHandler $largePRHandler;

    public function __construct(WarnLargePRHandler $largePRHandler)
    {
        $this->largePRHandler = $largePRHandler;
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
        return isset($PRLargeEvent['pull_request']['additions']) && isset($PRLargeEvent['pull_request']['deletions']);
    }

    private function updatePR(array $PRLargeEvent): void
    {
        $command = new WarnLargePR();
        $command->PRIdentifier = $this->getPRIdentifier($PRLargeEvent);
        $command->repositoryIdentifier = $PRLargeEvent['repository']['full_name'];
        $command->additions = $PRLargeEvent['pull_request']['additions'];
        $command->deletions = $PRLargeEvent['pull_request']['deletions'];
        $this->largePRHandler->handle($command);
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
