<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Drivers\Tests\ProxyDriver;
use BotMan\Studio\Testing\BotManTester;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class ListenForNewPRToReviewTest extends KernelTestCase
{
    /** @var BotManTester */
    private $botTester;

    public function setUp(): void
    {
        parent::setUp();
//        $this->get('slub.infrastructure.chat.slack.put_pr_to_review_action')->execute();

        DriverManager::loadDriver(ProxyDriver::class);
        $fakeDriver = new FakeDriver();
        ProxyDriver::setInstance($fakeDriver);

//        $bot = SlubBotFactory->createBot([]);
//        Hash::driver('bcrypt')->setRounds(4);

//        $this->botTester = new BotmanTester($bot, $fakeDriver);
    }

    public function testExecute()
    {
        $this->assertTrue(true);
//        $this->botTester->receives('TR please') ->assertReplyNothing();
    }
}
