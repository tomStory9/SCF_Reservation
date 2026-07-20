<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720013853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add user status';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD user_status VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP user_status');
    }
}
