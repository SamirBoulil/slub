<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Slack\SlackDriver;
use Slub\Application\PutPRToReview\PutPRToReview;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class ListenForNewPRToReview
{
    /** @var array */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function execute(): void
    {
        $botMan = $this->createBot();

        $botMan->listen();
    }

    private function createBot(): BotMan
    {
        DriverManager::loadDriver(SlackDriver::class);

        return BotManFactory::create($this->config);
    }
}
