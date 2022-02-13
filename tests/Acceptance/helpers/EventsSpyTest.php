<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\BuildLink;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Reviewer\ReviewerName;
use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIPending;
use Slub\Domain\Event\CIRed;
use Slub\Domain\Event\GoodToMerge;
use Slub\Domain\Event\PRClosed;
use Slub\Domain\Event\PRCommented;
use Slub\Domain\Event\PRGTMed;
use Slub\Domain\Event\PRMerged;
use Slub\Domain\Event\PRNotGTMed;
use Slub\Domain\Event\PRPutToReview;
use Slub\Domain\Event\PRTooLarge;

class EventsSpyTest extends TestCase
{
    private EventsSpy $eventSpy;

    public function setUp(): void/* The :void return type declaration that should be here would cause a BC issue */
    {
        $this->eventSpy = new EventsSpy();
    }

    /**
     * @test
     */
    public function it_tells_wether_the_gtm_event_has_been_thrown(): void
    {
        $this->assertFalse($this->eventSpy->PRGMTedDispatched());
        $this->eventSpy->notifyPRGTMed(
            PRGTMed::forPR(
                PRIdentifier::fromString('1010'),
                ReviewerName::fromString('samir')
            )
        );
        $this->assertTrue($this->eventSpy->PRGMTedDispatched());
    }

    /**
     * @test
     */
    public function it_tells_wether_the_not_gtm_event_has_been_thrown(): void
    {
        $this->assertFalse($this->eventSpy->PRNotGMTedDispatched());
        $this->eventSpy->notifyPRNotGTMed(
            PRNotGTMed::forPR(
                PRIdentifier::fromString('1010'),
                ReviewerName::fromString('samir')
            )
        );
        $this->assertTrue($this->eventSpy->PRNotGMTedDispatched());
    }

    /**
     * @test
     */
    public function it_tells_wether_the_commented_event_has_been_thrown(): void
    {
        $this->assertFalse($this->eventSpy->PRCommentedDispatched());
        $this->eventSpy->notifyPRCommented(
            PRCommented::forPR(
                PRIdentifier::fromString('1010'),
                ReviewerName::fromString('samir')
            )
        );
        $this->assertTrue($this->eventSpy->PRCommentedDispatched());
    }

    /**
     * @test
     */
    public function it_tells_wether_the_ci_green_event_has_been_thrown(): void
    {
        $this->assertFalse($this->eventSpy->CIGreenEventDispatched());
        $this->eventSpy->notifyCIGreen(
            CIGreen::forPR(
                PRIdentifier::fromString('1010'),
            )
        );
        $this->assertTrue($this->eventSpy->CIGreenEventDispatched());
    }

    /**
     * @test
     */
    public function it_tells_wether_the_ci_red_event_has_been_thrown(): void
    {
        $this->assertFalse($this->eventSpy->CIRedEventDispatched());
        $this->eventSpy->notifyCIRed(
            CIRed::forPR(
                PRIdentifier::fromString('1010'),
                BuildLink::none()
            )
        );
        $this->assertTrue($this->eventSpy->CIRedEventDispatched());
    }

    /**
     * @test
     */
    public function it_tells_wether_the_pr_has_been_closed_event_has_been_thrown(): void
    {
        $this->assertFalse($this->eventSpy->PRClosedDispatched());
        $this->eventSpy->notifyPRClosed(PRClosed::forPR(PRIdentifier::fromString('1010')));
        $this->assertTrue($this->eventSpy->PRClosedDispatched());
    }

    /**
     * @test
     */
    public function it_tells_wether_the_good_to_merge_event_has_been_thrown(): void
    {
        $this->assertFalse($this->eventSpy->PRGoodToMergeDispatched());
        $this->eventSpy->notifyPRGoodToMerge(
            GoodToMerge::forPR(
                PRIdentifier::fromString('1010'),
            )
        );
        $this->assertTrue($this->eventSpy->PRGoodToMergeDispatched());
    }

    /**
     * @test
     */
    public function it_tells_if_it_has_events(): void
    {
        $this->assertFalse($this->eventSpy->hasEvents());
        $this->eventSpy->notifyCIRed(CIRed::forPR(PRIdentifier::fromString('1010'), BuildLink::none()));
        $this->assertTrue($this->eventSpy->hasEvents());
    }

    /** @test */
    public function it_resets_the_spy(): void
    {
        $this->eventSpy->notifyCIRed(CIRed::forPR(PRIdentifier::fromString('1010'), BuildLink::none()));
        $this->assertTrue($this->eventSpy->hasEvents());

        $this->eventSpy->reset();

        $this->assertFalse($this->eventSpy->hasEvents());
    }
    // To fix
    // to add

    /**
     * @test
     */
    public function it_subscribes_to_events(): void
    {
        $this->assertEquals(
            [
                PRGTMed::class => 'notifyPRGTMed',
                PRNotGTMed::class => 'notifyPRNotGTMed',
                PRCommented::class => 'notifyPRCommented',
                CIGreen::class => 'notifyCIGreen',
                CIRed::class => 'notifyCIRed',
                PRMerged::class => 'notifyPRMerged',
                PRPutToReview::class => 'notifyPRPutToReview',
                CIPending::class => 'notifyCIPending',
                PRClosed::class => 'notifyPRClosed',
                GoodToMerge::class => 'notifyPRGoodToMerge',
                PRTooLarge::class => 'notifyPRTooLarge'
            ],
            EventsSpy::getSubscribedEvents()
        );
    }
}
