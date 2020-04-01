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
class CheckRunEventHandler implements EventHandlerInterface
{
    private const CHECK_RUN_EVENT_TYPE = 'check_run';

    /** @var CIStatusUpdateHandler */
    private $CIStatusUpdateHandler;

    /** @var GetPRInfoInterface */
    private $getPRInfo;

    public function __construct(
        CIStatusUpdateHandler $CIStatusUpdateHandler,
        GetPRInfoInterface $getPRInfo
    ) {
        $this->CIStatusUpdateHandler = $CIStatusUpdateHandler;
        $this->getPRInfo = $getPRInfo;
    }

    public function supports(string $eventType): bool
    {
        return self::CHECK_RUN_EVENT_TYPE === $eventType;
    }

    public function handle(array $checkRunEvent): void
    {
        $PRIdentifier = $this->getPRIdentifier($checkRunEvent);
        $PRInfo = $this->getCIStatusFromGithub($PRIdentifier);

        $command = new CIStatusUpdate();
        $command->PRIdentifier = $PRIdentifier->stringValue();
        $command->repositoryIdentifier = $checkRunEvent['repository']['full_name'];
        $command->status = $PRInfo->CIStatus->status;
        $command->buildLink = $PRInfo->CIStatus->buildLink;
        $this->CIStatusUpdateHandler->handle($command);
    }

    private function getPRIdentifier(array $CIStatusUpdate): PRIdentifier
    {
        return PRIdentifier::fromString(
            sprintf(
                '%s/%s',
                $CIStatusUpdate['repository']['full_name'],
                $CIStatusUpdate['check_run']['check_suite']['pull_requests'][0]['number']
            )
        );
    }

    private function getCIStatusFromGithub(PRIdentifier $PRIdentifier): PRInfo
    {
        return $this->getPRInfo->fetch($PRIdentifier);
    }
}
