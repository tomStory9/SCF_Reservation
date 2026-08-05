<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804055623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ajout du tarif dans UserRole';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_role ADD tarif VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_role DROP tarif');
    }
}
