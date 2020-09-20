<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create a column for the workspace identifiers.
 */
final class Version20200919114739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create a column for the workspace identifiers.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pr ADD WORKSPACE_IDS JSON;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pr DROP WORKSPACE_IDS;');
    }
}
