<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIRed;
use Slub\Domain\Event\PRMerged;

class PRTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_PR_and_normalizes_itself()
    {
        $pr = PR::create(PRIdentifier::create('akeneo/pim-community-dev/1111'));

        $this->assertSame(
            [
                'identifier' => 'akeneo/pim-community-dev/1111',
                'GTM'        => 0,
                'NOT_GTM'    => 0,
                'CI_STATUS'  => 'PENDING',
                'IS_MERGED'  => false,
            ],
            $pr->normalize()
        );
    }

    /**
     * @test
     */
    public function it_is_created_from_normalized()
    {
        $normalizedPR = [
            'identifier' => 'akeneo/pim-community-dev/1111',
            'GTM'        => 2,
            'NOT_GTM'    => 0,
            'CI_STATUS'  => 'GREEN',
            'IS_MERGED'  => true,
        ];

        $pr = PR::fromNormalized($normalizedPR);

        $this->assertSame($normalizedPR, $pr->normalize());
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
        $pr = PR::create(PRIdentifier::create('akeneo/pim-community-dev/1111'));
        $this->assertEquals(0, $pr->normalize()['GTM']);

        $pr->GTM();
        $this->assertEquals(1, $pr->normalize()['GTM']);

        $pr->GTM();
        $this->assertEquals(2, $pr->normalize()['GTM']);
    }

    /**
     * @test
     */
    public function it_can_be_NOT_GTM_multiple_times()
    {
        $pr = PR::create(PRIdentifier::create('akeneo/pim-community-dev/1111'));
        $this->assertEquals(0, $pr->normalize()['NOT_GTM']);

        $pr->notGTM();
        $this->assertEquals(1, $pr->normalize()['NOT_GTM']);

        $pr->notGTM();
        $this->assertEquals(2, $pr->normalize()['NOT_GTM']);
    }

    /**
     * @test
     */
    public function it_can_become_green()
    {
        $pr = PR::create(PRIdentifier::fromString('akeneo/pim-community-dev/1111'));
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
        $pr = PR::create(PRIdentifier::fromString('akeneo/pim-community-dev/1111'));
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
        $pr = PR::create(PRIdentifier::fromString('akeneo/pim-community-dev/1111'));
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

        $pr = PR::create($identifier);

        $this->assertTrue($pr->PRIdentifier()->equals($identifier));
    }

    public function normalizedWithMissingInformation(): array
    {
        return [
            'Missing identifier'     => [
                [
                    'GTM'       => 0,
                    'NOT_GTM'   => 0,
                    'CI_STATUS' => 'PENDING',
                    'IS_MERGED' => false,
                ],
            ],
            'Missing GTM'            => [
                [
                    'identifier' => 'akeneo/pim-community-dev/1111',
                    'NOT_GTM'    => 0,
                    'CI_STATUS'  => 'PENDING',
                    'IS_MERGED'  => false,
                ],
            ],
            'Missing NOT GTM'        => [
                [
                    'identifier' => 'akeneo/pim-community-dev/1111',
                    'GTM'        => 0,
                    'CI_STATUS'  => 'PENDING',
                    'IS_MERGED'  => false,
                ],
            ],
            'Missing CI status'      => [
                [
                    'identifier' => 'akeneo/pim-community-dev/1111',
                    'GTM'        => 0,
                    'NOT_GTM'    => 0,
                    'IS_MERGED'  => false,

                ],
            ],
            'Missing is merged flag' => [
                [
                    'identifier' => 'akeneo/pim-community-dev/1111',
                    'GTM'        => 0,
                    'NOT_GTM'    => 0,
                    'CI_STATUS'  => 'PENDING',
                ],
            ],
        ];
    }
}
