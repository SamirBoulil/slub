<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Query;

use Doctrine\DBAL\Connection;
use Slub\Infrastructure\Persistence\Sql\Query\ShouldCommunicateInApp;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class ShouldCommunicateInAppTest extends KernelTestCase
{
    private const WORKSPACE_ID = 'workspace_id';
    private const USER_ID = 'user_id';

    private ShouldCommunicateInApp $shouldCommunicateInApp;
    private Connection $connection;

    public function setUp(): void
    {
        parent::setUp();
        $this->shouldCommunicateInApp = $this->get('slub.infrastructure.persistence.should_communicate_in_app');
        $this->connection = $this->get('slub.infrastructure.persistence.sql.database_connection');
        $this->resetDB();
    }

    public function test_it_should_communicate_about_new_slash_command_if_an_old_user_has_not_received_a_message_yet(): void
    {
        $this->givenTheUserExistsInTheWorkspaceAndHasNotReceivedTheMessage();

        $this->assertTrue($this->shouldCommunicateInApp->messageAboutNewSlashCommand(self::WORKSPACE_ID, self::USER_ID));
    }

    public function test_it_does_NOT_communicate_about_new_slash_command_if_an_old_user_has_already_received_the_message(): void
    {
        $this->givenTheUserExistsInTheWorkspaceAndHasReceivedTheMessage();
        $this->assertFalse($this->shouldCommunicateInApp->messageAboutNewSlashCommand(self::WORKSPACE_ID, self::USER_ID));
    }

    public function test_it_does_NOT_communicate_about_new_slash_command_if_a_new_user_has_already_received_the_message(): void
    {
        $this->assertFalse($this->shouldCommunicateInApp->messageAboutNewSlashCommand(self::WORKSPACE_ID, self::USER_ID));
    }

    private function givenTheUserExistsInTheWorkspaceAndHasNotReceivedTheMessage(): void
    {
        $this->insertInAppCommunicationInformation(false);
    }

    private function givenTheUserExistsInTheWorkspaceAndHasReceivedTheMessage(): void
    {
        $this->insertInAppCommunicationInformation(true);
    }

    private function insertInAppCommunicationInformation(bool $hasReceivedTheMessage): void
    {
        $worspaceId = self::WORKSPACE_ID;
        $userId = self::USER_ID;
        $hasReceivedTheMessageValue = $hasReceivedTheMessage ? '1' : '0';
        $sql = <<<SQL
INSERT INTO `user_in_app_communication` (`WORKSPACE_ID`, `USER_ID`, `NEW_SLASH_COMMAND_RELEASE_COMMUNICATION_COUNT`)
VALUES
	('${worspaceId}', '${userId}', {$hasReceivedTheMessageValue});
SQL;
        $this->connection->executeStatement($sql);
    }

    private function resetDB(): void
    {
        $this->connection->executeStatement('TRUNCATE TABLE user_in_app_communication;');
    }
}
