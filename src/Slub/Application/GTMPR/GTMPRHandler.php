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
    private $prRepository;

    /** @var IsSupportedInterface */
    private $isSupported;

    /** @var PRGTMedNotifyMany */
    private $PRGTMedNotifyMany;

    public function __construct(
        PRRepositoryInterface $prRepository,
        IsSupportedInterface $isRepositorySupported,
        PRGTMedNotifyMany $PRGTMedNotifyMany
    ) {
        $this->prRepository = $prRepository;
        $this->isSupported = $isRepositorySupported;
        $this->PRGTMedNotifyMany = $PRGTMedNotifyMany;
    }

    public function handle(GTMPR $command)
    {
        $pr = $this->prRepository->getBy(PRIdentifier::create($command->repository, $command->prIdentifier));
        $pr->GTM();
        $this->prRepository->save($pr);
        $this->PRGTMedNotifyMany->PRGTMed(PRGTMed::withIdentifier($pr->identifier()));
    }
}
