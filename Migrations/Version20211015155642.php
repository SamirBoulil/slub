<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the slack app installation table to store access tokens.
 */
final class Version20211015155642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate message identifier & channel identifier to add the workspace identifier';
    }

    public function up(Schema $schema): void
    {
        $allPRs = <<<SQL
SELECT IDENTIFIER, MESSAGE_IDS, CHANNEL_IDS, WORKSPACE_IDS FROM pr;
SQL;
        $statement = $this->connection->executeQuery($allPRs);
        $result = $statement->fetchAll();
        foreach ($result as $pr) {
            if ($this->isPRdirty($pr)) {
                continue;
            }
            $pr = $this->migrateMessageIdentifiers($pr);
            $pr = $this->migrateChannelIdentifiers($pr);
            $sqlUpdate = <<<SQL
INSERT INTO
  pr (IDENTIFIER, MESSAGE_IDS, CHANNEL_IDS)
VALUES
  ('${pr['IDENTIFIER']}', '${pr['MESSAGE_IDS']}', '${pr['CHANNEL_IDS']}')
ON DUPLICATE KEY UPDATE
  IDENTIFIER = '${pr['IDENTIFIER']}',
  MESSAGE_IDS = '${pr['MESSAGE_IDS']}',
  CHANNEL_IDS = '${pr['CHANNEL_IDS']}';
SQL;
            $this->addSql($sqlUpdate);
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }

    private function migrateMessageIdentifiers($pr): array
    {
        $pr['MESSAGE_IDS'] = json_encode(
            array_map(
                static fn(string $messageIdentifier): string => sprintf(
                    '%s@%s',
                    current(json_decode($pr['WORKSPACE_IDS'])),
                    $messageIdentifier
                ),
                json_decode($pr['MESSAGE_IDS'], false)
            )
        );

        return $pr;
    }

    private function migrateChannelIdentifiers(array $pr): array
    {
        $pr['CHANNEL_IDS'] = json_encode(
            array_map(
                static fn(string $messageIdentifier): string => sprintf(
                    '%s@%s',
                    current(json_decode($pr['WORKSPACE_IDS'])),
                    $messageIdentifier
                ),
                json_decode($pr['CHANNEL_IDS'], false)
            )
        );

        return $pr;
    }

    /**
     * @param $pr
     *
     * @return bool
     *
     */
    private function isPRdirty($pr): bool
    {
        return null === $pr['WORKSPACE_IDS'] || null === $pr['CHANNEL_IDS'] || null === $pr['WORKSPACE_IDS'];
}
}
