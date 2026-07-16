<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716055217 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout colonne periode dans TimeSlot';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE time_slot ADD period VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE time_slot DROP period');
    }
}
