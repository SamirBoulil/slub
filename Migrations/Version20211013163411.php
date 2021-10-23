<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create a column for is large boolean.
 */
final class Version20211013163411 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create a column for is large boolean.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pr ADD IS_TOO_LARGE BOOLEAN DEFAULT false;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pr DROP IS_TOO_LARGE;');
    }
}
