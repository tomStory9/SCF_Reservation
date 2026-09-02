<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902201034 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'suppression map ling et text pour description zone';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE facility DROP map_link');
        $this->addSql('ALTER TABLE zone ALTER jp_desc TYPE TEXT');
        $this->addSql('ALTER TABLE zone ALTER en_desc TYPE TEXT');
        $this->addSql('ALTER TABLE zone ALTER fr_desc TYPE TEXT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE facility ADD map_link TEXT NOT NULL');
        $this->addSql('ALTER TABLE zone ALTER jp_desc TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE zone ALTER en_desc TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE zone ALTER fr_desc TYPE VARCHAR(255)');
    }
}
