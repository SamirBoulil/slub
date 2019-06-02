<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Application\MergedPR\MergedPR;
use Slub\Application\MergedPR\MergedPRHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class PRMergedEventHandler implements EventHandlerInterface
{
    private const PULL_REQUEST_EVENT_TYPE = 'pull_request';

    /** @var MergedPRHandler */
    private $mergedPRHandler;

    public function __construct(MergedPRHandler $mergedPRHandler)
    {
        $this->mergedPRHandler = $mergedPRHandler;
    }

    public function supports(string $eventType): bool
    {
        return self::PULL_REQUEST_EVENT_TYPE === $eventType;
    }

    public function handle(array $PRMergedEvent): void
    {
        if ($this->isPullRequestEventSupported($PRMergedEvent)) {
            $this->updatePR($PRMergedEvent);
        }
    }

    private function isPullRequestEventSupported(array $PRMergedEvent): bool
    {
        $isMerged = $PRMergedEvent['pull_request']['merged'] ?? false;

        return $isMerged;
    }

    private function updatePR(array $CIStatusUpdate): void
    {
        $command = new MergedPR();
        $command->PRIdentifier = $this->getPRIdentifier($CIStatusUpdate);
        $command->repositoryIdentifier = $CIStatusUpdate['repository']['full_name'];
        $this->mergedPRHandler->handle($command);
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
