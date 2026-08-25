<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826064830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enregistre la date d’acceptation des CGU pour chaque réservation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD terms_accepted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking DROP terms_accepted_at');
    }
}
