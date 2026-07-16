<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716071438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du numéro de jour à WeekDay';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE week_day ADD day_number INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE week_day DROP day_number');
    }
}
