<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIRed;

class PRTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_PR()
    {
        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');

        $pr = PR::create($identifier);

        $this->assertNotNull($pr);
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
        ];

        $pr = PR::fromNormalized($normalizedPR);

        $this->assertSame($normalizedPR, $pr->normalize());
    }

    /**
     * @test
     */
    public function it_can_be_GTM_multiple_times()
    {
        $pr = PR::create(PRIdentifier::create('akeneo/pim-community-dev/1111'));
        $this->assertEquals(
            [
                'identifier' => 'akeneo/pim-community-dev/1111',
                'GTM'        => 0,
                'NOT_GTM'    => 0,
                'CI_STATUS'  => 'NO_STATUS',
            ],
            $pr->normalize()
        );

        $pr->GTM();
        $this->assertEquals(
            [
                'identifier' => 'akeneo/pim-community-dev/1111',
                'GTM'        => 1,
                'NOT_GTM'    => 0,
                'CI_STATUS'  => 'NO_STATUS',
            ],
            $pr->normalize()
        );

        $pr->GTM();
        $this->assertEquals(
            [
                'identifier' => 'akeneo/pim-community-dev/1111',
                'GTM'        => 2,
                'NOT_GTM'    => 0,
                'CI_STATUS'  => 'NO_STATUS',
            ],
            $pr->normalize()
        );
    }

    /**
     * @test
     */
    public function it_can_be_NOT_GTM_multiple_times()
    {
        $pr = PR::create(PRIdentifier::create('akeneo/pim-community-dev/1111'));
        $this->assertEquals(
            [
                'identifier' => 'akeneo/pim-community-dev/1111',
                'GTM'        => 0,
                'NOT_GTM'    => 0,
                'CI_STATUS'  => 'NO_STATUS',
            ],
            $pr->normalize()
        );

        $pr->notGTM();
        $this->assertEquals(
            [
                'identifier' => 'akeneo/pim-community-dev/1111',
                'GTM'        => 0,
                'NOT_GTM'    => 1,
                'CI_STATUS'  => 'NO_STATUS',
            ],
            $pr->normalize()
        );

        $pr->notGTM();
        $this->assertEquals(
            [
                'identifier' => 'akeneo/pim-community-dev/1111',
                'GTM'        => 0,
                'NOT_GTM'    => 2,
                'CI_STATUS'  => 'NO_STATUS',
            ],
            $pr->normalize()
        );
    }

    /**
     * @test
     */
    public function it_can_become_green()
    {
        $pr = PR::fromNormalized(
            [
                'identifier' => 'akeneo/pim-community-dev/1111',
                'GTM'        => 2,
                'NOT_GTM'    => 0,
                'CI_STATUS'  => 'GREEN',
            ]
        );
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
        $pr = PR::fromNormalized(
            [
                'identifier' => 'akeneo/pim-community-dev/1111',
                'GTM'        => 2,
                'NOT_GTM'    => 0,
                'CI_STATUS'  => 'GREEN',
            ]
        );
        $pr->red();
        $this->assertEquals($pr->normalize()['CI_STATUS'], 'RED');
        $this->assertCount(1, $pr->getEvents());
        $this->assertInstanceOf(CIRed::class, current($pr->getEvents()));
    }

    /**
     * @test
     */
    public function it_normalizes_itself()
    {
        $pr = PR::create(PRIdentifier::create('akeneo/pim-community-dev/1111'));

        $this->assertSame(
            [
                'identifier' => 'akeneo/pim-community-dev/1111',
                'GTM'        => 0,
                'NOT_GTM'    => 0,
                'CI_STATUS'  => 'NO_STATUS',
            ],
            $pr->normalize()
        );
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
}
