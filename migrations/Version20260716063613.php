<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716063613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ajout de la date de création booking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP created_date');
    }
}
