<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;
use Slub\Infrastructure\VCS\Github\Query\FindPRNumberInterface;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class StatusUpdatedEventHandler implements EventHandlerInterface
{
    private const STATUS_UPDATE_EVENT_TYPE = 'status';

    /** @var CIStatusUpdateHandler */
    private $CIStatusUpdateHandler;

    /** @var FindPRNumberInterface */
    private $findPRNumber;

    /** @var GetCIStatus */
    private $getCIStatus;

    /** @var string[] */
    private $supportedStatusNames;

    public function __construct(
        CIStatusUpdateHandler $CIStatusUpdateHandler,
        FindPRNumberInterface $findPRNumber,
        GetCIStatus $getCIStatus,
        string $supportedStatusNames
    ) {
        $this->CIStatusUpdateHandler = $CIStatusUpdateHandler;
        $this->findPRNumber = $findPRNumber;
        $this->supportedStatusNames = explode(',', $supportedStatusNames);
        $this->getCIStatus = $getCIStatus;
    }

    public function supports(string $eventType): bool
    {
        return self::STATUS_UPDATE_EVENT_TYPE === $eventType;
    }

    public function handle(array $statusUpdate): void
    {
        if ($this->isStatusGreenButNotSupported($statusUpdate)) {
            return;
        }
        $this->updateCIStatus($statusUpdate);
    }

    private function isStatusGreenButNotSupported(array $statusUpdate): bool
    {
        $isGreen = 'GREEN' === $this->statusCheckStatus($statusUpdate);
        $isSupported = in_array($statusUpdate['name'], $this->supportedStatusNames);

        return $isGreen && !$isSupported;
    }

    private function statusCheckStatus(array $statusUpdate): string
    {
        $status = $statusUpdate['state'];
        switch ($status) {
            case 'pending':
                return 'PENDING';
            case 'success':
                return 'GREEN';
            case 'error':
            case 'failure':
                return 'RED';
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported status "%s"', $status));
        }
    }

    private function updateCIStatus(array $CIStatusUpdate): void
    {
        $command = new CIStatusUpdate();
        $command->PRIdentifier = $this->getPRIdentifier($CIStatusUpdate)->stringValue();
        $command->repositoryIdentifier = $CIStatusUpdate['repository']['full_name'];
        $checkStatus = $this->getCIStatusFromGithub($this->getPRIdentifier($CIStatusUpdate), $CIStatusUpdate['sha']);
        $command->status = $checkStatus->status;
        $command->buildLink = $checkStatus->buildLink;

        $this->CIStatusUpdateHandler->handle($command);
    }

    private function getPRIdentifier(array $CIStatusUpdate): PRIdentifier
    {
        $PRNumber = $this->findPRNumber->fetch($CIStatusUpdate['name'], $CIStatusUpdate['sha']);

        return PRIdentifier::fromString(sprintf('%s/%s', $CIStatusUpdate['repository']['full_name'], $PRNumber));
    }

    private function getCIStatusFromGithub(PRIdentifier $PRIdentifier, $commitRef): CheckStatus
    {
        return $this->getCIStatus->fetch($PRIdentifier, $commitRef);
    }
}
