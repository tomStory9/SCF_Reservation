<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime le lien de carte des sites et normalise les périodes bloquées à l’heure pleine';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE blockout_period SET start_date = DATE_TRUNC('hour', start_date), end_date = CASE WHEN end_date = DATE_TRUNC('hour', end_date) THEN end_date ELSE DATE_TRUNC('hour', end_date) + INTERVAL '1 hour' END");
        $this->addSql('ALTER TABLE facility DROP map_link');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE facility ADD map_link TEXT DEFAULT 'todo' NOT NULL");
    }
}
