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

    public function __construct(
        CIStatusUpdateHandler $CIStatusUpdateHandler,
        FindPRNumberInterface $findPRNumber,
        GetCIStatus $getCIStatus
    ) {
        $this->CIStatusUpdateHandler = $CIStatusUpdateHandler;
        $this->findPRNumber = $findPRNumber;
        $this->getCIStatus = $getCIStatus;
    }

    public function supports(string $eventType): bool
    {
        return self::STATUS_UPDATE_EVENT_TYPE === $eventType;
    }

    public function handle(array $statusUpdate): void
    {
        $command = new CIStatusUpdate();
        $command->PRIdentifier = $this->getPRIdentifier($statusUpdate)->stringValue();
        $command->repositoryIdentifier = $statusUpdate['repository']['full_name'];
        $checkStatus = $this->getCIStatusFromGithub($this->getPRIdentifier($statusUpdate), $statusUpdate['sha']);
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
