<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\UI\CLI;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Slub\Infrastructure\UI\CLI\PurgeDeliveredEventsCLI;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PurgeDeliveredEventsCLITest extends KernelTestCase
{
    private const COMMAND_NAME = 'slub:maintenance:purge-delivered-events';

    /** @var CommandTester */
    private $commandTester;

    /** * @var Connection */
    private $connection;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->get('slub.infrastructure.persistence.sql.database_connection');
        $this->setUpCommand();
    }

    /**
     * @test
     */
    public function it_purges_the_delivered_events_from_the_database()
    {
        $this->assertThereAreSomeDeliveredEventsInDatabase();

        $this->commandTester->execute(['command' => self::COMMAND_NAME]);

        self::assertEquals(0, $this->countDeliveredEvents());
        $this->assertContains('Starting to purge the delivered events from the database', $this->commandTester->getDisplay());
        $this->assertContains('✅ Purge of 10 delivered events done', $this->commandTester->getDisplay());
    }

    private function setUpCommand(): void
    {
        $application = new Application(self::$kernel);
        $application->add(new PurgeDeliveredEventsCLI($this->connection));
        $command = $application->find(self::COMMAND_NAME);
        $this->commandTester = new CommandTester($command);
    }

    private function assertThereAreSomeDeliveredEventsInDatabase()
    {
        $insertDeliveredEvents = <<<SQL
DELETE FROM delivered_event;
INSERT INTO `delivered_event` (`IDENTIFIER`)
VALUES
	('0005749e-57a9-11ea-9df8-b7c318c617e1'),
	('0005b1a0-58fa-11ea-9690-b5025a8b4292'),
	('000b975a-5175-11ea-8d08-7ea416782cd1'),
	('00108080-57b5-11ea-8fc5-bfc7a42051f7'),
	('0012c2ae-54af-11ea-8ca7-9bac98c89850'),
	('0013702a-57d7-11ea-8000-dd9734c99b11'),
	('0014df86-5c60-11ea-8cc3-f7affe564e6e'),
	('001744a0-57e3-11ea-86d5-ce3644695109'),
	('00195550-5492-11ea-8293-71730b19411e'),
	('001d94f2-5a47-11ea-9f98-cbbfc877fdad');
SQL;
        $this->connection->executeUpdate($insertDeliveredEvents);
        $numberOfEvents = $this->countDeliveredEvents();
        self::assertGreaterThan(0, $numberOfEvents);
    }

    private function countDeliveredEvents(): int
    {
        $result = $this->connection
            ->executeQuery('SELECT COUNT(*) FROM delivered_event;')
            ->fetch(\PDO::FETCH_COLUMN);

        $count = $this->connection->convertToPHPValue($result, Type::INTEGER);

        return $count;
    }
}
