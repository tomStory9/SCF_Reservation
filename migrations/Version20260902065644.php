<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902065644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'update transation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction ADD stripe_payment_intent_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction ALTER stripe_fee DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction DROP stripe_payment_intent_id');
        $this->addSql('ALTER TABLE transaction ALTER stripe_fee SET NOT NULL');
    }
}
