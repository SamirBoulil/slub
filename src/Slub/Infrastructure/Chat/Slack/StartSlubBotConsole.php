<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class StartSlubBotConsole extends Command
{
    use LockableTrait;

    protected static $defaultName = 'slub:slack:start-bot';

    /** @var SlubBot */
    private $slubBot;

    public function __construct(SlubBot $slubBot)
    {
        parent::__construct(self::$defaultName);

        $this->slubBot = $slubBot;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Starts the slack bot')
            ->setHelp('This command allows you to start the slack bot.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }

        $this->slubBot->start();
        $output->writeln('Slub bot started for chat "Slack"');

        return 0;
    }
}
