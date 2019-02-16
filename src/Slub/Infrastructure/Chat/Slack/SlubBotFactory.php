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
class SlubBotFactory
{
    /** @var array */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function createBot(): Botman
    {
        DriverManager::loadDriver(SlackDriver::class);
        $bot = BotManFactory::create($this->config);
        $bot = $this->listensToNewPR($bot);

        return $bot;
    }

    private function listensToNewPR(BotMan $bot): BotMan
    {
        $bot->hears('TR please', function (Botman $bot) {
//            $bot->getMessage()->getSender();
            $prToReview = new PutPRToReview();
            $prToReview->PRIdentifier = 'akeneo/pim-community-dev/1010';
            $prToReview->repositoryIdentifier = 'akeneo/pim-community-dev';
            $prToReview->channelIdentifier = 'squad-raccoons';
        });

        return $bot;
    }
}
