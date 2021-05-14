<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210324161048 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE memo ADD mem_crea_date DATETIME NOT NULL, ADD mem_modif_date DATETIME NOT NULL, DROP mail_crea_date, DROP mail_modif_date');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE memo ADD mail_crea_date DATETIME NOT NULL, ADD mail_modif_date DATETIME NOT NULL, DROP mem_crea_date, DROP mem_modif_date');
    }
}
