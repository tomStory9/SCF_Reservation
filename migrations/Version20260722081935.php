<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260722081935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du nombre de jour de réservation en avance dans userRole';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_role ADD max_advance_booking_days INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_role DROP max_advance_booking_days');
    }
}
