<?php

declare(strict_types=1);

namespace Slub\Application\UnpublishPR;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class UnpublishPRHandler
{
    public function __construct(private PRRepositoryInterface $PRRepository, private LoggerInterface $logger)
    {
    }

    public function handle(UnpublishPR $unpublishPR): void
    {
        $PRIdentifierToUnpublish = PRIdentifier::fromString($unpublishPR->PRIdentifier);
        $this->PRRepository->unpublishPR($PRIdentifierToUnpublish);
        $this->logger->info(sprintf('PR "%s" has been unpublished', $PRIdentifierToUnpublish->stringValue()));
    }
}
