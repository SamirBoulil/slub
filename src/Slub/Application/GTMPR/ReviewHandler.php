<?php

declare(strict_types=1);

namespace Slub\Application\GTMPR;

use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Event\PRNotGTMed;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        IsSupportedInterface $isSupported,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->PRRepository = $PRRepository;
        $this->isSupported = $isSupported;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handle(Review $review)
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::create($review->PRIdentifier));
        if ($review->isGTM) {
            $PR->GTM();
            $this->eventDispatcher->dispatch(PRGTMed::class, PRGTMed::withIdentifier($PR->PRIdentifier()));
        } else {
            $PR->notGTM();
            $this->eventDispatcher->dispatch(PRNotGTMed::class, PRNotGTMed::withIdentifier($PR->PRIdentifier()));
        }

        $this->PRRepository->save($PR);
        $this->dispatchEvents($PR);
    }

    private function dispatchEvents(PR $PR): void
    {
    }
}
