<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260817095709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du setting pour valider un utilisateur automatiquement ou non';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings ADD is_user_validation_required BOOLEAN NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings DROP is_user_validation_required');
    }
}
