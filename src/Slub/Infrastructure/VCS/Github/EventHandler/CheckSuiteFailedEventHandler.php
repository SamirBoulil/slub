<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class CheckSuiteFailedEventHandler implements EventHandlerInterface
{
    private const CHECK_SUITE_EVENT_TYPE = 'check_suite';

    /** @var CIStatusUpdateHandler */
    private $CIStatusUpdateHandler;

    public function __construct(CIStatusUpdateHandler $CIStatusUpdateHandler)
    {
        $this->CIStatusUpdateHandler = $CIStatusUpdateHandler;
    }

    public function supports(string $eventType): bool
    {
        return self::CHECK_SUITE_EVENT_TYPE === $eventType;
    }

    public function handle(Request $request): void
    {
        $checkSuiteUpdate = $this->checkSuiteUpdate($request);
        if ($this->isCheckSuiteFailing($checkSuiteUpdate)) {
            $this->updateCIStatusToFail($checkSuiteUpdate);
        }
    }

    private function checkSuiteUpdate(Request $request): array
    {
        return json_decode((string) $request->getContent(), true);
    }

    private function isCheckSuiteFailing(array $CIStatusUpdate): bool
    {
        return 'completed' === $CIStatusUpdate['action'] && 'failure' === $CIStatusUpdate['check_suite']['conclusion'];
    }

    private function updateCIStatusToFail(array $checkSuiteUpdate): void
    {
        $command = new CIStatusUpdate();
        $command->PRIdentifier = $this->getPRIdentifier($checkSuiteUpdate);
        $command->repositoryIdentifier = $checkSuiteUpdate['repository']['full_name'];
        $command->isGreen = false;
        $this->CIStatusUpdateHandler->handle($command);
    }

    private function getPRIdentifier(array $CIStatusUpdate): string
    {
        return sprintf(
            '%s/%s',
            $CIStatusUpdate['repository']['full_name'],
            $CIStatusUpdate['check_suite']['pull_requests'][0]['number']
        );
    }
}
