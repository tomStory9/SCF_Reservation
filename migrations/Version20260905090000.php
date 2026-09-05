<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalise les périodes bloquées à l’heure pleine';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE blockout_period SET start_date = DATE_TRUNC('hour', start_date), end_date = CASE WHEN end_date = DATE_TRUNC('hour', end_date) THEN end_date ELSE DATE_TRUNC('hour', end_date) + INTERVAL '1 hour' END");
    }

    public function down(Schema $schema): void
    {
    }
}
