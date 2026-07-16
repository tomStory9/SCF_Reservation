<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716074553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajout d'un code pour les emplacement (pour la recherche autrement que par le nom)";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ADD code VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location DROP code');
    }
}
