<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260821070852 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE settings ADD min_day_booking INT NOT NULL');
        $this->addSql('ALTER TABLE settings ADD min_day_room_booking INT NOT NULL');
        $this->addSql('ALTER TABLE settings ADD is_pending_booking_blocking BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE settings ADD hour_check_in_room INT NOT NULL');
        $this->addSql('ALTER TABLE settings ADD hour_check_out INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE settings DROP min_day_booking');
        $this->addSql('ALTER TABLE settings DROP min_day_room_booking');
        $this->addSql('ALTER TABLE settings DROP is_pending_booking_blocking');
        $this->addSql('ALTER TABLE settings DROP hour_check_in_room');
        $this->addSql('ALTER TABLE settings DROP hour_check_out');
    }
}
