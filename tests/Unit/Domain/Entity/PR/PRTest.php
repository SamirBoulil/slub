<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIRed;
use Slub\Domain\Event\PRMerged;
use Slub\Domain\Event\PRPutToReview;

class PRTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_PR_and_normalizes_itself()
    {
        $expectedPRIdentifier = PRIdentifier::create('akeneo/pim-community-dev/1111');
        $expectedMessageIdentifier = MessageIdentifier::fromString('1');

        $pr = PR::create($expectedPRIdentifier, $expectedMessageIdentifier, 0, 0, 0, 'pending', false);

        $this->assertSame(
            [
                'IDENTIFIER'  => 'akeneo/pim-community-dev/1111',
                'GTMS'        => 0,
                'NOT_GTMS'    => 0,
                'COMMENTS'    => 0,
                'CI_STATUS'   => 'PENDING',
                'IS_MERGED'   => false,
                'MESSAGE_IDS' => ['1'],
            ],
            $pr->normalize()
        );
        $this->assertPRPutToReviewEvent($pr->getEvents(), $expectedPRIdentifier, $expectedMessageIdentifier);
    }

    /**
     * @test
     */
    public function it_is_created_from_normalized()
    {
        $normalizedPR = [
            'IDENTIFIER'  => 'akeneo/pim-community-dev/1111',
            'GTMS'        => 2,
            'NOT_GTMS'    => 0,
            'COMMENTS'    => 0,
            'CI_STATUS'   => 'GREEN',
            'IS_MERGED'   => true,
            'MESSAGE_IDS' => ['1', '2'],
        ];

        $pr = PR::fromNormalized($normalizedPR);

        $this->assertSame($normalizedPR, $pr->normalize());
        $this->assertEmpty($pr->getEvents());
    }

    /**
     * @test
     * @dataProvider normalizedWithMissingInformation
     */
    public function it_throws_if_there_is_not_enough_information_to_create_from_normalized(
        array $normalizedWithMissingInformation
    ) {
        $this->expectException(\InvalidArgumentException::class);
        PR::fromNormalized($normalizedWithMissingInformation);
    }

    /**
     * @test
     */
    public function it_can_be_GTM_multiple_times()
    {
        $pr = PR::create(
            PRIdentifier::create('akeneo/pim-community-dev/1111'), MessageIdentifier::fromString('1'), 0, 0, 0, 'pending', false
        );
        $this->assertEquals(0, $pr->normalize()['GTMS']);

        $pr->GTM();
        $this->assertEquals(1, $pr->normalize()['GTMS']);

        $pr->GTM();
        $this->assertEquals(2, $pr->normalize()['GTMS']);
    }

    /**
     * @test
     */
    public function it_can_be_NOT_GTM_multiple_times()
    {
        $pr = PR::create(
            PRIdentifier::create('akeneo/pim-community-dev/1111'), MessageIdentifier::fromString('1'), 0, 0, 0, 'pending', false
        );
        $this->assertEquals(0, $pr->normalize()['NOT_GTMS']);

        $pr->notGTM();
        $this->assertEquals(1, $pr->normalize()['NOT_GTMS']);

        $pr->notGTM();
        $this->assertEquals(2, $pr->normalize()['NOT_GTMS']);
    }

    /**
     * @test
     */
    public function it_can_be_commented_multiple_times()
    {
        $pr = PR::create(
            PRIdentifier::create('akeneo/pim-community-dev/1111'), MessageIdentifier::fromString('1'), 0, 0, 0, 'pending', false
        );
        $this->assertEquals(0, $pr->normalize()['COMMENTS']);

        $pr->comment();
        $this->assertEquals(1, $pr->normalize()['COMMENTS']);

        $pr->comment();
        $this->assertEquals(2, $pr->normalize()['COMMENTS']);
    }

    /**
     * @test
     */
    public function it_can_become_green()
    {
        $pr = PR::fromNormalized([
            'IDENTIFIER'  => 'akeneo/pim-community-dev/1111',
            'GTMS'        => 0,
            'NOT_GTMS'    => 0,
            'COMMENTS'    => 0,
            'CI_STATUS'   => 'PENDING',
            'IS_MERGED'   => false,
            'MESSAGE_IDS' => ['1'],
        ]);

        $pr->green();

        $this->assertEquals($pr->normalize()['CI_STATUS'], 'GREEN');
        $this->assertCount(1, $pr->getEvents());
        $this->assertInstanceOf(CIGreen::class, current($pr->getEvents()));
    }

    /**
     * @test
     */
    public function it_can_become_red()
    {
        $pr = PR::fromNormalized([
            'IDENTIFIER'  => 'akeneo/pim-community-dev/1111',
            'GTMS'        => 0,
            'NOT_GTMS'    => 0,
            'COMMENTS'    => 0,
            'CI_STATUS'   => 'PENDING',
            'IS_MERGED'   => false,
            'MESSAGE_IDS' => ['1'],
        ]);

        $pr->red();

        $this->assertEquals($pr->normalize()['CI_STATUS'], 'RED');
        $this->assertCount(1, $pr->getEvents());
        $this->assertInstanceOf(CIRed::class, current($pr->getEvents()));
    }

    /**
     * @test
     */
    public function it_can_be_merged()
    {
        $pr = PR::fromNormalized([
            'IDENTIFIER'  => 'akeneo/pim-community-dev/1111',
            'GTMS'        => 0,
            'NOT_GTMS'    => 0,
            'COMMENTS'    => 0,
            'CI_STATUS'   => 'PENDING',
            'IS_MERGED'   => false,
            'MESSAGE_IDS' => ['1'],
        ]);

        $pr->merged();

        $this->assertEquals($pr->normalize()['IS_MERGED'], true);
        $this->assertCount(1, $pr->getEvents());
        $this->assertInstanceOf(PRMerged::class, current($pr->getEvents()));
    }

    /**
     * @test
     */
    public function it_returns_its_identifier()
    {
        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');

        $pr = PR::create($identifier, MessageIdentifier::fromString('1'), 0, 0, 0, 'pending', false);

        $this->assertTrue($pr->PRIdentifier()->equals($identifier));
    }

    /**
     * @test
     */
    public function it_returns_the_message_ids()
    {
        $pr = PR::create(
            PRIdentifier::create('akeneo/pim-community-dev/1111'), MessageIdentifier::fromString('1'), 0, 0, 0, 'pending', false
        );
        $this->assertEquals('1', current($pr->messageIdentifiers())->stringValue());
    }

    /**
     * @test
     */
    public function it_can_be_put_to_review_multiple_times()
    {
        $pr = PR::fromNormalized([
            'IDENTIFIER'  => 'akeneo/pim-community-dev/1111',
            'GTMS'        => 2,
            'NOT_GTMS'    => 0,
            'COMMENTS'    => 0,
            'CI_STATUS'   => 'GREEN',
            'IS_MERGED'   => true,
            'MESSAGE_IDS' => ['1'],
        ]);
        $expectedMessageId = MessageIdentifier::create('2');

        $pr->putToReviewAgainViaMessage($expectedMessageId);

        $this->assertEquals($pr->normalize()['MESSAGE_IDS'], ['1', '2']);
        $this->assertPRPutToReviewEvent(
            $pr->getEvents(),
            PRIdentifier::fromString('akeneo/pim-community-dev/1111'),
            $expectedMessageId
        );
    }

    /**
     * @test
     */
    public function it_can_be_put_to_review_multiple_times_with_the_same_message()
    {
        $pr = PR::fromNormalized([
            'IDENTIFIER'  => 'akeneo/pim-community-dev/1111',
            'GTMS'        => 0,
            'NOT_GTMS'    => 0,
            'COMMENTS'    => 0,
            'CI_STATUS'   => 'PENDING',
            'IS_MERGED'   => false,
            'MESSAGE_IDS' => ['1'],
        ]);

        $pr->putToReviewAgainViaMessage(MessageIdentifier::create('1'));

        $this->assertEquals($pr->normalize()['MESSAGE_IDS'], ['1']);
        $this->assertEmpty($pr->getEvents());
    }

    public function normalizedWithMissingInformation(): array
    {
        return [
            'Missing identifier'     => [
                [
                    'GTMS'      => 0,
                    'NOT_GTMS'  => 0,
                    'CI_STATUS' => 'PENDING',
                    'IS_MERGED' => false,
                ],
            ],
            'Missing GTMS'           => [
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/1111',
                    'NOT_GTMS'   => 0,
                    'CI_STATUS'  => 'PENDING',
                    'IS_MERGED'  => false,
                ],
            ],
            'Missing NOT GTMS'       => [
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/1111',
                    'GTMS'       => 0,
                    'CI_STATUS'  => 'PENDING',
                    'IS_MERGED'  => false,
                ],
            ],
            'Missing CI status'      => [
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/1111',
                    'GTMS'       => 0,
                    'NOT_GTMS'   => 0,
                    'IS_MERGED'  => false,

                ],
            ],
            'Missing is merged flag' => [
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/1111',
                    'GTMS'       => 0,
                    'NOT_GTMS'   => 0,
                    'CI_STATUS'  => 'PENDING',
                ],
            ],
        ];
    }

    /**
     * @param array $events
     * @param PRIdentifier $expectedPRIdentifier
     * @param MessageIdentifier $expectedMessageId
     *
     */
    private function assertPRPutToReviewEvent(array $events, PRIdentifier $expectedPRIdentifier, MessageIdentifier $expectedMessageId): void
    {
        $this->assertCount(1, $events);
        $PRPutToReviewEvent = current($events);
        $this->assertInstanceOf(PRPutToReview::class, $PRPutToReviewEvent);
        $this->assertTrue($PRPutToReviewEvent->PRIdentifier()->equals($expectedPRIdentifier));
        $this->assertTrue($PRPutToReviewEvent->messageIdentifier()->equals($expectedMessageId));
    }
}
