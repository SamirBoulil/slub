<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class CheckRunSuccessEventHandler implements EventHandlerInterface
{
    private const CHECK_RUN_EVENT_TYPE = 'check_run';

    /** @var string[] */
    private $supportedCheckRunNames;

    /** @var CIStatusUpdateHandler */
    private $CIStatusUpdateHandler;

    public function __construct(CIStatusUpdateHandler $CIStatusUpdateHandler, string $supportedCheckRunNames)
    {
        $this->CIStatusUpdateHandler = $CIStatusUpdateHandler;
        $this->supportedCheckRunNames = explode(',', $supportedCheckRunNames);
    }

    public function supports(string $eventType): bool
    {
        return self::CHECK_RUN_EVENT_TYPE === $eventType;
    }

    public function handle(Request $request): void
    {
        $CIStatusUpdate = $this->getCIStatusUpdate($request);
        if ($this->isCIStatusUpdateSupported($CIStatusUpdate)) {
            $this->updateCIStatus($CIStatusUpdate);
        }
    }

    private function getCIStatusUpdate(Request $request): array
    {
        return json_decode((string) $request->getContent(), true);
    }

    private function isCIStatusUpdateSupported(array $CIStatusUpdate): bool
    {
        if ('completed' !== $CIStatusUpdate['action']) {
            return false;
        }
        $conclusion = $CIStatusUpdate['check_run']['conclusion'];
        if ('success' === $conclusion) {
            return in_array($CIStatusUpdate['check_run']['name'], $this->supportedCheckRunNames);
        }

        return 'failure' === $conclusion;
    }

    private function updateCIStatus(array $CIStatusUpdate): void
    {
        $command = new CIStatusUpdate();
        $command->PRIdentifier = $this->getPRIdentifier($CIStatusUpdate);
        $command->repositoryIdentifier = $CIStatusUpdate['repository']['full_name'];
        $command->isGreen = $this->isGreen($CIStatusUpdate);
        $this->CIStatusUpdateHandler->handle($command);
    }

    private function getPRIdentifier(array $CIStatusUpdate): string
    {
        return sprintf(
            '%s/%s',
            $CIStatusUpdate['repository']['full_name'],
            $CIStatusUpdate['check_run']['check_suite']['pull_requests'][0]['number']
        );
    }

    private function isGreen(array $CIStatusUpdate): bool
    {
        return 'success' === $CIStatusUpdate['check_run']['conclusion'];
    }
}
