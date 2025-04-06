<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Psr\Log\LoggerInterface;
use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CIStatus;
use Slub\Infrastructure\VCS\Github\Query\FindPRNumberInterface;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class StatusUpdatedEventHandler implements EventHandlerInterface
{
    private const STATUS_NAME = 'context';

    private const STATUS_UPDATE_EVENT_TYPE = 'status';
    private array $checksBlacklist;

    public function __construct(
        private CIStatusUpdateHandler $CIStatusUpdateHandler,
        private FindPRNumberInterface $findPRNumber,
        private GetCIStatus $getCIStatus,
        private LoggerInterface $logger,
        string $checksBlacklist,
    ) {
        $this->checksBlacklist = explode(',', $checksBlacklist);
    }

    public function supports(string $eventType, array $eventPayload): bool
    {
        return self::STATUS_UPDATE_EVENT_TYPE === $eventType && $this->isNotBlacklisted($eventPayload);
    }

    public function handle(array $statusUpdate): void
    {
        $command = new CIStatusUpdate();
        $PRIdentifier = $this->getPRIdentifier($statusUpdate);
        $command->PRIdentifier = $PRIdentifier->stringValue();
        $command->repositoryIdentifier = $statusUpdate['repository']['full_name'];
        $checkStatus = $this->getCIStatusFromGithub($PRIdentifier, $statusUpdate['sha']);
        $command->status = $checkStatus->status;
        $command->buildLink = $checkStatus->buildLink;

        $this->CIStatusUpdateHandler->handle($command);
    }

    private function getPRIdentifier(array $CIStatusUpdate): PRIdentifier
    {
//        $this->logger->critical(sprintf('Fetching PRNumber for Status update event: %s', (string) json_encode($CIStatusUpdate)));
        $PRNumber = $this->findPRNumber->fetch($CIStatusUpdate['name'], $CIStatusUpdate['sha']);
        if ($PRNumber === null) {
            throw new \RuntimeException(sprintf('Impossible to fetch PR number for commit on repository %s', $CIStatusUpdate['name']));
        }

        return PRIdentifier::fromPRInfo($CIStatusUpdate['repository']['full_name'], $PRNumber);
    }

    private function getCIStatusFromGithub(PRIdentifier $PRIdentifier, $commitRef): CIStatus
    {
        return $this->getCIStatus->fetch($PRIdentifier, $commitRef);
    }

    private function isNotBlacklisted(array $eventPayload): bool
    {
        return empty(
            \array_filter(
                $this->checksBlacklist,
                static fn(string $blacklistedCheck) => \preg_match(
                    sprintf('/%s/', $eventPayload[self::STATUS_NAME] ?? ''),
                    $blacklistedCheck
                )
            )
        );
    }
}
