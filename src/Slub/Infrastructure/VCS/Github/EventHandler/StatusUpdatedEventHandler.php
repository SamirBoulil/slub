<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Psr\Log\LoggerInterface;
use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\GitHub\Payload\Status;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CIStatus;
use Slub\Infrastructure\VCS\Github\Query\FindPRNumberInterface;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class StatusUpdatedEventHandler implements EventHandlerInterface
{
    private const STATUS_UPDATE_EVENT_TYPE = 'status';

    public function __construct(
        private readonly CIStatusUpdateHandler $CIStatusUpdateHandler,
        private readonly FindPRNumberInterface $findPRNumber,
        private readonly GetCIStatus $getCIStatus,
        private readonly LoggerInterface $logger,
        private readonly array $excludedNames = [],
    ) {
    }

    public function supports(string $eventType): bool
    {
        return self::STATUS_UPDATE_EVENT_TYPE === $eventType;
    }

    public function handle(array $request): void
    {
        $status = Status::fromPayload($request);

        if ($this->isExcluded($status)) {
            $this->logger->info(sprintf('Excluded Status update event: %s', $status->name));

            return;
        }

        $PRIdentifier = $this->getPRIdentifier($status);

        $command = new CIStatusUpdate();
        $command->PRIdentifier = $PRIdentifier->stringValue();
        $command->repositoryIdentifier = $status->repository->fullName;

        $checkStatus = $this->getCIStatusFromGithub($PRIdentifier, $status->sha);

        $command->status = $checkStatus->status;
        $command->buildLink = $checkStatus->buildLink;

        $this->CIStatusUpdateHandler->handle($command);
    }

    private function getPRIdentifier(Status $status): PRIdentifier
    {
//        $this->logger->critical(sprintf('Fetching PRNumber for Status update event: %s', (string) json_encode($CIStatusUpdate)));
        $PRNumber = $this->findPRNumber->fetch($status->name, $status->sha);
        if ($PRNumber === null) {
            throw new \RuntimeException(sprintf('Impossible to fetch PR number for commit on repository %s', $CIStatusUpdate['name']));
        }

        return PRIdentifier::fromPRInfo($status->repository->fullName, $PRNumber);
    }

    private function getCIStatusFromGithub(PRIdentifier $PRIdentifier, $commitRef): CIStatus
    {
        return $this->getCIStatus->fetch($PRIdentifier, $commitRef);
    }

    private function isExcluded(Status $status): bool
    {
        if (in_array($status->name, $this->excludedNames)) {
            return true;
        }

        foreach ($this->excludedNames as $excludedNameAsPattern) {
            if (preg_match(sprintf('/%s/', $excludedNameAsPattern), $status->name)) {
                return true;
            }
        }

        return false;
    }
}
