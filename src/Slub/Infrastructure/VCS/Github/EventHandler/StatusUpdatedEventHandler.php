<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class StatusUpdatedEventHandler implements EventHandlerInterface
{
    private const STATUS_UPDATE_EVENT_TYPE = 'status';

    /** @var string[] */
    private $supportedStatusNames;

    /** @var CIStatusUpdateHandler */
    private $CIStatusUpdateHandler;

    public function __construct(CIStatusUpdateHandler $CIStatusUpdateHandler, string $supportedStatusNames)
    {
        $this->CIStatusUpdateHandler = $CIStatusUpdateHandler;
        $this->supportedStatusNames = explode(',', $supportedStatusNames);
    }

    public function supports(string $eventType): bool
    {
        return self::STATUS_UPDATE_EVENT_TYPE === $eventType;
    }

    public function handle(Request $request): void
    {
        $statusUpdate = $this->getStatusUpdate($request);
        if ($this->isStatusGreenButNotSupported($statusUpdate)) {
            return;
        }

        $this->updateCIStatus($statusUpdate);
    }

    private function isStatusGreenButNotSupported(array $statusUpdate): bool
    {
        $isGreen = 'GREEN' === $this->getStatus($statusUpdate);
        $isSupported = in_array($statusUpdate['name'], $this->supportedStatusNames);

        return $isGreen && !$isSupported;
    }

    private function getStatusUpdate(Request $request): array
    {
        return json_decode((string) $request->getContent(), true);
    }

    private function updateCIStatus(array $CIStatusUpdate): void
    {
        $command = new CIStatusUpdate();
        $command->PRIdentifier = $this->getPRIdentifier($CIStatusUpdate);
        $command->repositoryIdentifier = $CIStatusUpdate['repository']['full_name'];
        $command->status = $this->getStatus($CIStatusUpdate);
        $this->CIStatusUpdateHandler->handle($command);
    }

    private function getPRIdentifier(array $CIStatusUpdate): string
    {
        return sprintf(
            '%s/%s',
            $CIStatusUpdate['repository']['full_name'],
            $CIStatusUpdate['number']
        );
    }

    private function getStatus(array $statusUpdate): string
    {
        $status = $statusUpdate['state'];
        switch ($status) {
            case 'pending' :
                return 'PENDING';
            case 'success' :
                return 'GREEN';
            case 'error' :
            case 'failure' :
                return 'RED';
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported status "%s"', $status));
        }
    }
}
