<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\BuildLink;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Reviewer\ReviewerName;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIPending;
use Slub\Domain\Event\CIRed;
use Slub\Domain\Event\GoodToMerge;
use Slub\Domain\Event\PRClosed;
use Slub\Domain\Event\PRMerged;
use Slub\Domain\Event\PRPutToReview;
use Slub\Domain\Event\PRTooLarge;

class PRTest extends TestCase
{
    private const A_TIMESTAMP = '1560177798';
    private const PR_IDENTIFIER = 'akeneo/pim-community-dev/1111';

    /**
     * @test
     */
    public function it_creates_a_PR_and_normalizes_itself(): void
    {
        $prIdentifier = self::PR_IDENTIFIER;
        $channelIdentifier = 'squad-raccoons';
        $workspaceIdentifier = 'akeneo';
        $messageId = '1';
        $author = 'sam';
        $title = 'Add new feature';
        $expectedPRIdentifier = PRIdentifier::create($prIdentifier);
        $expectedChannelIdentifier = ChannelIdentifier::fromString($channelIdentifier);
        $expectedWorkspaceIdentifier = WorkspaceIdentifier::fromString($workspaceIdentifier);
        $expectedMessageIdentifier = MessageIdentifier::fromString($messageId);

        $pr = PR::create(
            $expectedPRIdentifier,
            $expectedChannelIdentifier,
            $expectedWorkspaceIdentifier,
            $expectedMessageIdentifier,
            AuthorIdentifier::fromString($author),
            Title::fromString($title),
            0,
            0,
            0,
            'PENDING',
            false,
            true // Too large
        );

        $normalizedPR = $pr->normalize();
        self::assertEquals($prIdentifier, $normalizedPR['IDENTIFIER']);
        self::assertEquals($author, $normalizedPR['AUTHOR_IDENTIFIER']);
        self::assertEquals($title, $normalizedPR['TITLE']);
        self::assertEquals(0, $normalizedPR['GTMS']);
        self::assertEquals(0, $normalizedPR['NOT_GTMS']);
        self::assertEquals(0, $normalizedPR['COMMENTS']);
        self::assertEquals('PENDING', $normalizedPR['CI_STATUS']['BUILD_RESULT']);
        self::assertEquals('', $normalizedPR['CI_STATUS']['BUILD_LINK']);
        self::assertEquals(false, $normalizedPR['IS_MERGED']);
        self::assertEquals([$channelIdentifier], $normalizedPR['CHANNEL_IDS']);
        self::assertEquals([$workspaceIdentifier], $normalizedPR['WORKSPACE_IDS']);
        self::assertEquals([$messageId], $normalizedPR['MESSAGE_IDS']);
        self::assertNotEmpty($normalizedPR['PUT_TO_REVIEW_AT']);
        self::assertEmpty($normalizedPR['CLOSED_AT']);
        self::assertEquals(true, $normalizedPR['IS_TOO_LARGE']);
        $events = $pr->getEvents();
        self::assertCount(2, $events);
        $this->assertPRPutToReviewEvent($events, $expectedPRIdentifier, $expectedMessageIdentifier);
        $this->assertPRTooLargeEvent($events, $expectedPRIdentifier);
    }

    /**
     * @test
     */
    public function it_is_created_from_normalized(): void
    {
        $normalizedPR = [
            'IDENTIFIER' => self::PR_IDENTIFIER,
            'AUTHOR_IDENTIFIER' => 'sam',
            'TITLE' => 'Add new fixtures',
            'GTMS' => 2,
            'NOT_GTMS' => 0,
            'COMMENTS' => 0,
            'CI_STATUS' => [
                'BUILD_RESULT' => 'GREEN',
                'BUILD_LINK' => '',
            ],
            'IS_MERGED' => true,
            'CHANNEL_IDS' => ['squad-raccoons'],
            'WORKSPACE_IDS' => ['akeneo'],
            'MESSAGE_IDS' => ['1', '2'],
            'PUT_TO_REVIEW_AT' => self::A_TIMESTAMP,
            'CLOSED_AT' => self::A_TIMESTAMP,
            'IS_TOO_LARGE' => true
        ];

        $pr = PR::fromNormalized($normalizedPR);

        self::assertSame($normalizedPR, $pr->normalize());
        self::assertEmpty($pr->getEvents());
    }

    /**
     * @test
     * @dataProvider normalizedWithMissingInformation
     */
    public function it_throws_if_there_is_not_enough_information_to_create_from_normalized(
        array $normalizedWithMissingInformation
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        PR::fromNormalized($normalizedWithMissingInformation);
    }

    /**
     * @test
     */
    public function it_can_be_GTMed_multiple_times(): void
    {
        $pr = PR::create(
            PRIdentifier::create(self::PR_IDENTIFIER),
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $reviewerName = ReviewerName::fromString('samir');
        self::assertEquals(0, $pr->normalize()['GTMS']);

        $pr->GTM($reviewerName);
        self::assertEquals(1, $pr->normalize()['GTMS']);

        $pr->GTM($reviewerName);
        self::assertEquals(2, $pr->normalize()['GTMS']);
    }

    /**
     * @test
     */
    public function it_cannot_be_gtmed_once_it_is_closed(): void
    {
        $pr = PR::create(
            PRIdentifier::create(self::PR_IDENTIFIER),
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $pr->close(true);
        $reviewerName = ReviewerName::fromString('samir');
        self::assertEquals(0, $pr->normalize()['GTMS']);

        $pr->GTM($reviewerName);
        self::assertEquals(0, $pr->normalize()['GTMS']);
    }

    /**
     * @test
     */
    public function it_can_be_NOT_GTMed_multiple_times(): void
    {
        $pr = PR::create(
            PRIdentifier::create(self::PR_IDENTIFIER),
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $reviewerName = ReviewerName::fromString('samir');
        self::assertEquals(0, $pr->normalize()['NOT_GTMS']);

        $pr->notGTM($reviewerName);
        self::assertEquals(1, $pr->normalize()['NOT_GTMS']);

        $pr->notGTM($reviewerName);
        self::assertEquals(2, $pr->normalize()['NOT_GTMS']);
    }

    /**
     * @test
     */
    public function it_cannot_be_NOT_GTMed_once_it_is_closed(): void
    {
        $pr = PR::create(
            PRIdentifier::create(self::PR_IDENTIFIER),
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $pr->close(true);
        $reviewerName = ReviewerName::fromString('samir');
        self::assertEquals(0, $pr->normalize()['NOT_GTMS']);

        $pr->notGTM($reviewerName);
        self::assertEquals(0, $pr->normalize()['NOT_GTMS']);
    }

    /**
     * @test
     */
    public function it_can_be_commented_multiple_times(): void
    {
        $pr = PR::create(
            PRIdentifier::create(self::PR_IDENTIFIER),
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $reviewerName = ReviewerName::fromString('samir');
        self::assertEquals(0, $pr->normalize()['COMMENTS']);

        $pr->comment($reviewerName);
        self::assertEquals(1, $pr->normalize()['COMMENTS']);

        $pr->comment($reviewerName);
        self::assertEquals(2, $pr->normalize()['COMMENTS']);
    }

    /**
     * @test
     */
    public function it_cannot_be_commented_once_it_is_closed(): void
    {
        $pr = PR::create(
            PRIdentifier::create(self::PR_IDENTIFIER),
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $pr->close(true);
        $reviewerName = ReviewerName::fromString('samir');
        self::assertEquals(0, $pr->normalize()['COMMENTS']);

        $pr->comment($reviewerName);
        self::assertEquals(0, $pr->normalize()['COMMENTS']);
    }

    /**
     * @test
     */
    public function it_can_become_green(): void
    {
        $pr = $this->pendingPR();

        $pr->green();

        self::assertEquals($pr->normalize()['CI_STATUS']['BUILD_RESULT'], 'GREEN');
        self::assertCount(1, $pr->getEvents());
        $event = current($pr->getEvents());
        self::assertInstanceOf(CIGreen::class, $event);
        self::assertEquals(self::PR_IDENTIFIER, $event->PRIdentifier()->stringValue());
    }

    /**
     * @test
     */
    public function it_cannot_change_CI_status_once_it_is_closed(): void
    {
        $pr = PR::create(
            PRIdentifier::create(self::PR_IDENTIFIER),
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $pr->close(true);
        self::assertEquals('PENDING', $pr->normalize()['CI_STATUS']['BUILD_RESULT']);

        $pr->green();
        $pr->red(BuildLink::fromURL('https://travis.com/build/123'));
        self::assertEquals('PENDING', $pr->normalize()['CI_STATUS']['BUILD_RESULT']);
    }

    /**
     * @test
     */
    public function it_cannot_become_pending_once_it_is_closed(): void
    {
        $pr = PR::create(
            PRIdentifier::create(self::PR_IDENTIFIER),
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $pr->green();
        self::assertEquals('GREEN', $pr->normalize()['CI_STATUS']['BUILD_RESULT']);
        $pr->close(true);

        $pr->pending();
        self::assertEquals('GREEN', $pr->normalize()['CI_STATUS']['BUILD_RESULT']);
    }

    /**
     * @test
     */
    public function it_does_not_create_event_if_the_pr_is_already_green(): void
    {
        $pr = $this->greenPR();

        $pr->green();

        self::assertEmpty($pr->getEvents());
    }

    /**
     * @test
     */
    public function it_can_become_red_with_a_build_link(): void
    {
        $buildLink = 'https://build_link';
        $pr = $this->pendingPR();

        $pr->red(BuildLink::fromURL($buildLink));

        $CI_STATUS = $pr->normalize()['CI_STATUS'];
        self::assertEquals($CI_STATUS['BUILD_RESULT'], 'RED');
        self::assertEquals($CI_STATUS['BUILD_LINK'], $buildLink);
        self::assertCount(1, $pr->getEvents());
        $event = current($pr->getEvents());
        self::assertInstanceOf(CIRed::class, $event);
        self::assertEquals(self::PR_IDENTIFIER, $event->PRIdentifier()->stringValue());
        self::assertEquals($buildLink, $event->buildLink()->stringValue());
    }

    /**
     * @test
     */
    public function it_does_not_creates_event_if_the_pr_is_already_red(): void
    {
        $pr = $this->redPR();

        $pr->red(BuildLink::none());

        self::assertEmpty($pr->getEvents());
    }

    /**
     * @test
     */
    public function it_can_become_pending(): void
    {
        $pr = $this->greenPR();

        $pr->pending();

        self::assertEquals($pr->normalize()['CI_STATUS']['BUILD_RESULT'], 'PENDING');
        self::assertCount(1, $pr->getEvents());
        $event = current($pr->getEvents());
        self::assertInstanceOf(CIPending::class, $event);
        self::assertEquals(self::PR_IDENTIFIER, $event->PRIdentifier()->stringValue());
    }

    /**
     * @test
     */
    public function it_does_not_creates_event_if_the_pr_is_already_pending(): void
    {
        $pr = $this->pendingPR();

        $pr->pending();

        self::assertEmpty($pr->getEvents());
    }

    /**
     * @test
     */
    public function it_can_be_closed_and_merged(): void
    {
        $pr = $this->greenPR();

        $pr->close(true);

        self::assertNotEmpty($pr->normalize()['CLOSED_AT']);
        self::assertEquals(true, $pr->normalize()['IS_MERGED']);
        self::assertInstanceOf(PRMerged::class, current($pr->getEvents()));
        self::assertInstanceOf(PRClosed::class, last($pr->getEvents()));
    }

    /**
     * @test
     */
    public function it_can_be_closed_without_being_merged(): void
    {
        $pr = $this->greenPR();

        $pr->close(false);

        self::assertNotEmpty($pr->normalize()['CLOSED_AT']);
        self::assertFalse($pr->normalize()['IS_MERGED']);
        self::assertCount(1, $pr->getEvents());
        self::assertInstanceOf(PRClosed::class, current($pr->getEvents()));
    }

    /**
     * @test
     */
    public function when_the_pr_had_its_last_GTM_it_tells_the_PR_is_good_to_merge(): void
    {
        $prWithOneGTMMissing = PR::fromNormalized(
            [
                'IDENTIFIER' => self::PR_IDENTIFIER,
                'TITLE' => 'Add new feature',
                'AUTHOR_IDENTIFIER' => 'sam',
                'GTMS' => 1,
                'NOT_GTMS' => 0,
                'COMMENTS' => 0,
                'CI_STATUS' => [
                    'BUILD_RESULT' => 'GREEN',
                    'BUILD_LINK' => '',
                ],
                'IS_MERGED' => false,
                'CHANNEL_IDS' => ['squad-raccoons'],
                'WORKSPACE_IDS' => ['akeneo'],
                'MESSAGE_IDS' => ['1'],
                'PUT_TO_REVIEW_AT' => self::A_TIMESTAMP,
                'CLOSED_AT' => null,
                'IS_TOO_LARGE' => false,
            ]
        );

        $prWithOneGTMMissing->GTM(ReviewerName::fromString('samir'));

        self::assertCount(2, $prWithOneGTMMissing->getEvents());
        $event = last($prWithOneGTMMissing->getEvents());
        self::assertInstanceOf(GoodToMerge::class, $event);
    }

    /**
     * @test
     */
    public function when_the_pr_had_its_green_ci_it_tells_the_PR_is_good_to_merge(): void
    {
        $prWithOneGTMMissing = PR::fromNormalized(
            [
                'IDENTIFIER' => self::PR_IDENTIFIER,
                'TITLE' => 'Add new feature',
                'AUTHOR_IDENTIFIER' => 'sam',
                'GTMS' => 4,
                'NOT_GTMS' => 0,
                'COMMENTS' => 0,
                'CI_STATUS' => [
                    'BUILD_RESULT' => 'RED',
                    'BUILD_LINK' => '',
                ],
                'IS_MERGED' => false,
                'CHANNEL_IDS' => ['squad-raccoons'],
                'WORKSPACE_IDS' => ['akeneo'],
                'MESSAGE_IDS' => ['1'],
                'PUT_TO_REVIEW_AT' => self::A_TIMESTAMP,
                'CLOSED_AT' => null,
                'IS_TOO_LARGE' => false,
            ]
        );

        $prWithOneGTMMissing->green();

        self::assertCount(2, $prWithOneGTMMissing->getEvents());
        $event = last($prWithOneGTMMissing->getEvents());
        self::assertInstanceOf(GoodToMerge::class, $event);
    }

    /**
     * @test
     */
    public function it_returns_its_identifier(): void
    {
        $identifier = PRIdentifier::create(self::PR_IDENTIFIER);

        $pr = PR::create(
            $identifier,
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );

        self::assertTrue($pr->PRIdentifier()->equals($identifier));
    }

    /**
     * @test
     */
    public function it_returns_its_author_identifier(): void
    {
        $expectedAuthorIdentifier = 'sam';

        $pr = PR::create(
            PRIdentifier::create('akeneo/pim-community-dev'),
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString($expectedAuthorIdentifier),
            Title::fromString('Add new feature')
        );

        self::assertEquals($expectedAuthorIdentifier, $pr->authorIdentifier()->stringValue());
    }

    /**
     * @test
     */
    public function it_returns_its_title(): void
    {
        $expectedTitle = 'Add new feature';

        $pr = PR::create(
            PRIdentifier::create('akeneo/pim-community-dev'),
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString($expectedTitle)
        );

        self::assertEquals($expectedTitle, $pr->title()->stringValue());
    }

    /**
     * @test
     */
    public function it_returns_the_message_ids(): void
    {
        $pr = PR::create(
            PRIdentifier::create(self::PR_IDENTIFIER),
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        self::assertEquals('1', current($pr->messageIdentifiers())->stringValue());
    }

    /**
     * @test
     */
    public function it_returns_the_channel_ids(): void
    {
        $pr = PR::create(
            PRIdentifier::create(self::PR_IDENTIFIER),
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        self::assertEquals('squad-raccoons', current($pr->channelIdentifiers())->stringValue());
    }

    /**
     * @test
     */
    public function it_tells_the_number_of_days_the_pr_is_in_review(): void
    {
        $pr = PR::create(
            PRIdentifier::create(self::PR_IDENTIFIER),
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            MessageIdentifier::fromString('1'),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );

        self::assertEquals(0, $pr->numberOfDaysInReview());
    }

    /**
     * @test
     */
    public function it_can_be_put_to_review_multiple_times_in_different_channels(): void
    {
        $pr = $this->greenPR();
        $expectedMessageId = MessageIdentifier::create('2');

        $pr->putToReviewAgainViaMessage(ChannelIdentifier::fromString('brazil-team'), $expectedMessageId);

        self::assertEquals(['1', '2'], $pr->normalize()['MESSAGE_IDS']);
        $this->assertEquals($pr->normalize()['CHANNEL_IDS'], ['squad-raccoons', 'brazil-team']);
        $this->assertPRPutToReviewEvent(
            $pr->getEvents(),
            PRIdentifier::fromString(self::PR_IDENTIFIER),
            $expectedMessageId
        );
    }

    /**
     * @test
     */
    public function it_can_be_put_to_review_multiple_times_in_the_same_channel_with_the_same_message_id(): void
    {
        $pr = $this->pendingPR();

        $pr->putToReviewAgainViaMessage(ChannelIdentifier::fromString('squad-raccoons'),
            MessageIdentifier::create('1')
        );

        self::assertEquals($pr->normalize()['MESSAGE_IDS'], ['1']);
        $this->assertEquals($pr->normalize()['CHANNEL_IDS'], ['squad-raccoons']);
        self::assertEmpty($pr->getEvents());
    }

    /**
     * @test
     */
    public function it_can_be_reopened(): void
    {
        $pr = $this->closedPR();

        $pr->reopen();

        self::assertNull($pr->normalize()['CLOSED_AT']);
        self::assertFalse($pr->normalize()['IS_MERGED']);
        self::assertEmpty($pr->getEvents());
    }

    /**
     * @test
     */
    public function it_can_become_large(): void
    {
        $pr = $this->pendingPR();

        $pr->large();

        self::assertTrue($pr->normalize()['IS_TOO_LARGE']);
        self::assertCount(1, $pr->getEvents());
        $event = current($pr->getEvents());
        self::assertInstanceOf(PRTooLarge::class, $event);
        self::assertEquals(self::PR_IDENTIFIER, $event->PRIdentifier()->stringValue());
    }

    /**
     * @test
     */
    public function it_can_become_small(): void
    {
        $pr = $this->largePR();

        $pr->small();

        self::assertFalse($pr->normalize()['IS_TOO_LARGE']);
        self::assertCount(0, $pr->getEvents());
    }

    public function normalizedWithMissingInformation(): array
    {
        return [
            'Missing identifier' => [
                [
                    'GTMS' => 0,
                    'NOT_GTMS' => 0,
                    'CI_STATUS' => 'PENDING',
                    'IS_MERGED' => false,
                ],
            ],
            'Missing GTMS' => [
                [
                    'IDENTIFIER' => self::PR_IDENTIFIER,
                    'NOT_GTMS' => 0,
                    'CI_STATUS' => 'PENDING',
                    'IS_MERGED' => false,
                ],
            ],
            'Missing NOT GTMS' => [
                [
                    'IDENTIFIER' => self::PR_IDENTIFIER,
                    'GTMS' => 0,
                    'CI_STATUS' => 'PENDING',
                    'IS_MERGED' => false,
                ],
            ],
            'Missing CI status' => [
                [
                    'IDENTIFIER' => self::PR_IDENTIFIER,
                    'GTMS' => 0,
                    'NOT_GTMS' => 0,
                    'IS_MERGED' => false,
                ],
            ],
            'Missing is merged flag' => [
                [
                    'IDENTIFIER' => self::PR_IDENTIFIER,
                    'GTMS' => 0,
                    'NOT_GTMS' => 0,
                    'CI_STATUS' => 'PENDING',
                ],
            ],
        ];
    }

    private function assertPRPutToReviewEvent(
        array $events,
        PRIdentifier $expectedPRIdentifier,
        MessageIdentifier $expectedMessageId
    ): void {
        $PRPutToReviewEvent = current($events);
        self::assertInstanceOf(PRPutToReview::class, $PRPutToReviewEvent);
        self::assertTrue($PRPutToReviewEvent->PRIdentifier()->equals($expectedPRIdentifier));
        self::assertTrue($PRPutToReviewEvent->messageIdentifier()->equals($expectedMessageId));
    }

    private function assertPRTooLargeEvent(
        array $events,
        PRIdentifier $expectedPRIdentifier
    ): void {
        self::assertInstanceOf(PRTooLarge::class, last($events));
        self::assertTrue(current($events)->PRIdentifier()->equals($expectedPRIdentifier));
    }

    private function pendingPR(): PR
    {
        return PR::fromNormalized([
                'IDENTIFIER' => self::PR_IDENTIFIER,
                'TITLE' => 'Add new feature',
                'AUTHOR_IDENTIFIER' => 'sam',
                'GTMS' => 0,
                'NOT_GTMS' => 0,
                'COMMENTS' => 0,
                'CI_STATUS' => [
                    'BUILD_RESULT' => 'PENDING',
                    'BUILD_LINK' => '',
                ],
                'IS_MERGED' => false,
                'CHANNEL_IDS' => ['squad-raccoons'],
                'WORKSPACE_IDS' => ['akeneo'],
                'MESSAGE_IDS' => ['1'],
                'PUT_TO_REVIEW_AT' => self::A_TIMESTAMP,
                'CLOSED_AT' => null,
                'IS_TOO_LARGE' => false,
            ]
        );
    }

    private function largePR(): PR
    {
        return PR::fromNormalized([
                'IDENTIFIER' => self::PR_IDENTIFIER,
                'TITLE' => 'Add new feature',
                'AUTHOR_IDENTIFIER' => 'sam',
                'GTMS' => 0,
                'NOT_GTMS' => 0,
                'COMMENTS' => 0,
                'CI_STATUS' => [
                    'BUILD_RESULT' => 'PENDING',
                    'BUILD_LINK' => '',
                ],
                'IS_MERGED' => false,
                'CHANNEL_IDS' => ['squad-raccoons'],
                'WORKSPACE_IDS' => ['akeneo'],
                'MESSAGE_IDS' => ['1'],
                'PUT_TO_REVIEW_AT' => self::A_TIMESTAMP,
                'CLOSED_AT' => null,
                'IS_TOO_LARGE' => true,
            ]
        );
    }

    private function greenPR(): PR
    {
        return PR::fromNormalized(
            [
                'IDENTIFIER' => self::PR_IDENTIFIER,
                'TITLE' => 'Add new feature',
                'AUTHOR_IDENTIFIER' => 'sam',
                'GTMS' => 0,
                'NOT_GTMS' => 0,
                'COMMENTS' => 0,
                'CI_STATUS' => [
                    'BUILD_RESULT' => 'GREEN',
                    'BUILD_LINK' => '',
                ],
                'IS_MERGED' => false,
                'CHANNEL_IDS' => ['squad-raccoons'],
                'WORKSPACE_IDS' => ['akeneo'],
                'MESSAGE_IDS' => ['1'],
                'PUT_TO_REVIEW_AT' => self::A_TIMESTAMP,
                'CLOSED_AT' => null,
                'IS_TOO_LARGE' => false,
            ]
        );
    }

    private function redPR(): PR
    {
        return PR::fromNormalized(
            [
                'IDENTIFIER' => self::PR_IDENTIFIER,
                'TITLE' => 'Add new feature',
                'AUTHOR_IDENTIFIER' => 'sam',
                'GTMS' => 0,
                'NOT_GTMS' => 0,
                'COMMENTS' => 0,
                'CI_STATUS' => [
                    'BUILD_RESULT' => 'RED',
                    'BUILD_LINK' => '',
                ],
                'IS_MERGED' => false,
                'CHANNEL_IDS' => ['squad-raccoons'],
                'WORKSPACE_IDS' => ['akeneo'],
                'MESSAGE_IDS' => ['1'],
                'PUT_TO_REVIEW_AT' => self::A_TIMESTAMP,
                'CLOSED_AT' => null,
                'IS_TOO_LARGE' => false,
            ]
        );
    }

    private function closedPR(): PR
    {
        return PR::fromNormalized(
            [
                'IDENTIFIER' => self::PR_IDENTIFIER,
                'TITLE' => 'Add new feature',
                'AUTHOR_IDENTIFIER' => 'sam',
                'GTMS' => 0,
                'NOT_GTMS' => 0,
                'COMMENTS' => 0,
                'CI_STATUS' => [
                    'BUILD_RESULT' => 'RED',
                    'BUILD_LINK' => '',
                ],
                'IS_MERGED' => false,
                'CHANNEL_IDS' => ['squad-raccoons'],
                'WORKSPACE_IDS' => ['akeneo'],
                'MESSAGE_IDS' => ['1'],
                'PUT_TO_REVIEW_AT' => self::A_TIMESTAMP,
                'CLOSED_AT' => self::A_TIMESTAMP,
                'IS_TOO_LARGE' => false,
            ]
        );
    }
}
