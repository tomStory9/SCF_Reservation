<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819085126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Retrait max capacity dans zone';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone DROP max_capacity');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone ADD max_capacity INT DEFAULT NULL');
    }
}
