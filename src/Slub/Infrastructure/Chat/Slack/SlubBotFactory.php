<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Slack\SlackDriver;
use Slub\Application\PutPRToReview\PutPRToReview;
use Slub\Application\PutPRToReview\PutPRToReviewHandler;
use Slub\Domain\Repository\PRRepositoryInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class SlubBotFactory
{
    /** @var PutPRToReviewHandler */
    private $putPRToReviewHandler;

    /** @var array */
    private $config;

    public function __construct(PutPRToReviewHandler $putPRToReviewHandler, array $config)
    {
        $this->putPRToReviewHandler = $putPRToReviewHandler;
        $this->config = $config;
    }

    public function start(): Botman
    {
        DriverManager::loadDriver(SlackDriver::class);
        $bot = BotManFactory::create($this->config);
        $bot = $this->listensToNewPR($bot);

        return $bot;
    }

    private function listensToNewPR(BotMan $bot): BotMan
    {
        $bot->hears(
            'TR .* https://github.com/(.*)(/pull/.*)$',
            function (Botman $bot, string $repository, string $prSuffix) {
                $prToReview = new PutPRToReview();
                $prToReview->PRIdentifier = $repository . $prSuffix;
                $prToReview->repositoryIdentifier = $repository;
                $prToReview->channelIdentifier = 'squad-raccoons';

                $this->putPRToReviewHandler->handle($prToReview);
            }
        );

        return $bot;
    }
}
