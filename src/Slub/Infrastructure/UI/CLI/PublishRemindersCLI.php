<?php

declare(strict_types=1);

namespace Slub\Infrastructure\UI\CLI;

use Slub\Application\PublishReminders\PublishRemindersHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PublishRemindersCLI extends Command
{
    protected static $defaultName = 'slub:send-reminders';

    /** @var PublishRemindersHandler */
    private $publishRemindersHandler;

    public function __construct(PublishRemindersHandler $publishRemindersHandler)
    {
        parent::__construct(self::$defaultName);
        $this->publishRemindersHandler = $publishRemindersHandler;
    }

    protected function configure(): void
    {
        $this->setDescription('Send a reminder in each slack channel of the current PRs to review')
             ->setHidden(false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->publishRemindersHandler->handle();
        $output->writeln('<info>Reminders published!</info>');

        return null;
    }
}
