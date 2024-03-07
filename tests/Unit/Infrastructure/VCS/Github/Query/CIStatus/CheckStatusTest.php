<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\Query\CIStatus;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CIStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetStatusChecksStatus;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;
use Tests\WebTestCase;

class CheckStatusTest extends TestCase
{
    public function test_it_creates_check_statuses()
    {
        $name = 'name';
        $buildLink = 'my link';

        $green = CIStatus::green($name);
        $this->assertEquals($name, $green->name);
        $this->assertTrue($green->isGreen());
        $this->assertFalse($green->isRed());
        $this->assertFalse($green->isPending());

        $green = CIStatus::red($name, $buildLink);
        $this->assertEquals($name, $green->name);
        $this->assertTrue($green->isRed());
        $this->assertFalse($green->isGreen());
        $this->assertFalse($green->isPending());

        $green = CIStatus::pending($name);
        $this->assertEquals($name, $green->name);
        $this->assertTrue($green->isPending());
        $this->assertFalse($green->isRed());
        $this->assertFalse($green->isGreen());
    }
}
