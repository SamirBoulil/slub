<?php

declare(strict_types=1);

namespace Slub\Application\Review;

use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Event\PRNotGTMed;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class ReviewHandler
{
    /** @var PRRepositoryInterface */
    private $PRRepository;

    /** @var IsSupportedInterface */
    private $isSupported;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        IsSupportedInterface $isSupported
    ) {
        $this->PRRepository = $PRRepository;
        $this->isSupported = $isSupported;
    }

    public function handle(Review $review)
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::create($review->PRIdentifier));
        if ($review->isGTM) {
            $PR->GTM();
        } else {
            $PR->notGTM();
        }
        $this->PRRepository->save($PR);
    }
}
