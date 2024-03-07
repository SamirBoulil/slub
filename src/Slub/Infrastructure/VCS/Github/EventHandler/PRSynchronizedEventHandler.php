<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PRSynchronizedEventHandler implements EventHandlerInterface
{
    private const PULL_REQUEST_EVENT_TYPE = 'pull_request';
    public const PR_SYNCHRONIZED_ACTION = 'synchronize';

    public function __construct(private CIStatusUpdateHandler $CIStatusUpdateHandler)
    {
    }

    public function supports(string $eventType): bool
    {
        return self::PULL_REQUEST_EVENT_TYPE === $eventType;
    }

    public function handle(array $PRSynchronizedEvent): void
    {
        if ($this->isPRSynchronized($PRSynchronizedEvent)) {
            $this->updateCIStatus($PRSynchronizedEvent);
        }
    }

    private function isPRSynchronized(array $PRSynchronizedEvent): bool
    {
        return isset($PRSynchronizedEvent['action']) && self::PR_SYNCHRONIZED_ACTION === $PRSynchronizedEvent['action'];
    }

    private function updateCIStatus(array $PRSynchronizedEvent): void
    {
        $PRIdentifier = $this->getPRIdentifier($PRSynchronizedEvent);

        $command = new CIStatusUpdate();
        $command->PRIdentifier = $PRIdentifier->stringValue();
        $command->repositoryIdentifier = $PRSynchronizedEvent['repository']['full_name'];
        $command->status = 'PENDING';
        $command->buildLink = '';
        $this->CIStatusUpdateHandler->handle($command);
    }

    private function getPRIdentifier(array $PRSynchronizedEvent): PRIdentifier
    {
        return PRIdentifier::fromString(sprintf(
            '%s/%s',
            $PRSynchronizedEvent['repository']['full_name'],
            $PRSynchronizedEvent['pull_request']['number']
        ));
    }
}
