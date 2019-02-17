<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Slack\SlackDriver;
use Slub\Application\PutPRToReview\PutPRToReview;
use Slub\Application\PutPRToReview\PutPRToReviewHandler;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class SlubBot
{
    /** @var PutPRToReviewHandler */
    private $putPRToReviewHandler;

    /** @var array */
    private $config;

    /** @var BotMan */
    private $bot;

    public function __construct(PutPRToReviewHandler $putPRToReviewHandler, array $config)
    {
        $this->putPRToReviewHandler = $putPRToReviewHandler;
        $this->config = $config;
    }

    public function start(): Botman
    {
        if (null !== $this->bot) {
            throw new \LogicException('Slub bot is already started');
        }

        DriverManager::loadDriver(SlackDriver::class);
        $this->bot = BotManFactory::create($this->config);
        $this->listensToNewPR($this->bot);

        return $this->bot;
    }

    public function isStarted(): bool
    {
        return null !== $this->bot;
    }

    private function listensToNewPR(BotMan $bot): void
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
    }
}
