<?php

declare(strict_types=1);

namespace Slub\Application\GTMPR;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Event\PRNotGTMed;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class ReviewHandler
{
    /** @var PRRepositoryInterface */
    private $PRRepository;

    /** @var IsSupportedInterface */
    private $isSupported;

    /** @var PRGTMedNotifyMany */
    private $PRGTMedNotifyMany;

    /** @var PRNotGTMedNotifyMany */
    private $PRNotGTMedNotifyMany;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        IsSupportedInterface $isSupported,
        PRGTMedNotifyMany $PRGTMedNotifyMany,
        PRNotGTMedNotifyMany $PRNotGTMedNotifyMany
    ) {
        $this->PRRepository = $PRRepository;
        $this->isSupported = $isSupported;
        $this->PRGTMedNotifyMany = $PRGTMedNotifyMany;
        $this->PRNotGTMedNotifyMany = $PRNotGTMedNotifyMany;
    }

    public function handle(Review $review)
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::create($review->PRIdentifier));
        if ($review->isGTM) {
            $PR->GTM();
            $this->PRGTMedNotifyMany->notifyPRGTMed(PRGTMed::withIdentifier($PR->PRIdentifier()));
        } else {
            $PR->notGTM();
            $this->PRNotGTMedNotifyMany->notifyPRNotGTMed(PRNotGTMed::withIdentifier($PR->PRIdentifier()));
        }

        $this->PRRepository->save($PR);
    }
}
