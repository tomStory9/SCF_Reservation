<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715070236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout tarif reduit A et B dans le pricing';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pricing ADD reduced_price_a INT NOT NULL');
        $this->addSql('ALTER TABLE pricing ADD reduced_price_b INT NOT NULL');
        $this->addSql('ALTER TABLE pricing ADD guest_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pricing RENAME COLUMN price TO full_price');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pricing ADD price INT NOT NULL');
        $this->addSql('ALTER TABLE pricing DROP full_price');
        $this->addSql('ALTER TABLE pricing DROP reduced_price_a');
        $this->addSql('ALTER TABLE pricing DROP reduced_price_b');
        $this->addSql('ALTER TABLE pricing DROP guest_count');
    }
}
