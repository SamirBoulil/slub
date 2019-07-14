<?php

declare(strict_types=1);

namespace Slub\Infrastructure\UI\CLI;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SendRemindersCLI extends Command
{
    protected static $defaultName = 'slub:send-reminders';

    public function __construct(Connection $sqlConnection)
    {
        parent::__construct(self::$defaultName);
        $this->sqlConnection = $sqlConnection;
    }

    protected function configure(): void
    {
        $this->setDescription('Send a reminder in each slack channel of the current PRs to review')
             ->setHidden(false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
    }
}
