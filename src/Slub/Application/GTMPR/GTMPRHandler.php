<?php

declare(strict_types=1);

namespace Slub\Application\GTMPR;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class GTMPRHandler
{
    /** @var PRRepositoryInterface */
    private $PRRepository;

    /** @var IsSupportedInterface */
    private $isSupported;

    /** @var PRGTMedNotifyMany */
    private $PRGTMedNotifyMany;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        IsSupportedInterface $isSupported,
        PRGTMedNotifyMany $PRGTMedNotifyMany
    ) {
        $this->PRRepository = $PRRepository;
        $this->isSupported = $isSupported;
        $this->PRGTMedNotifyMany = $PRGTMedNotifyMany;
    }

    public function handle(GTMPR $command)
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::create($command->repositoryIdentifier, $command->PRIdentifier));
        $PR->GTM();
        $this->PRRepository->save($PR);
        $this->PRGTMedNotifyMany->notifyPRGTMed(PRGTMed::withIdentifier($PR->PRIdentifier()));
    }
}
