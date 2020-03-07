<?php

declare(strict_types=1);

namespace Slub\Infrastructure\UI\CLI;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeDeliveredEventsCLI extends Command
{
    protected static $defaultName = 'slub:maintenance:purge-delivered-events';

    /** @var Connection */
    private $connection;

    public function __construct(Connection $sqlConnection)
    {
        parent::__construct(self::$defaultName);
        $this->connection = $sqlConnection;
    }


    protected function configure(): void
    {
        $this->setDescription('Maintenance of operation consisting in purging the delivered events from the databases to keep its size minimal')
            ->setHidden(false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $output->writeln('<info>Starting to purge the delivered events from the database</info>');

        $numberOfDeliveredEventsToPurge = $this->countEvents();
        $this->purgeDeliveredEvents();

        $output->writeln('');
        $output->writeln(sprintf('<info>✅ Purge of %d delivered events done</info>', $numberOfDeliveredEventsToPurge));

        return null;
    }

    private function purgeDeliveredEvents(): void
    {
        $this->connection->executeUpdate('DELETE FROM delivered_event;');
    }

    private function countEvents(): int
    {
        $result = $this->connection
            ->executeQuery('SELECT COUNT(*) FROM delivered_event;')
            ->fetch(\PDO::FETCH_COLUMN);

        $count = $this->connection->convertToPHPValue($result, Type::INTEGER);

        return $count;
    }
}
