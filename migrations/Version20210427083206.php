<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210427083206 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE segment_filtre ADD champ_id INT DEFAULT NULL, DROP sf_champ');
        $this->addSql('ALTER TABLE segment_filtre ADD CONSTRAINT FK_855DB475D32AA90E FOREIGN KEY (champ_id) REFERENCES champ_requetable (id)');
        $this->addSql('CREATE INDEX IDX_855DB475D32AA90E ON segment_filtre (champ_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE segment_filtre DROP FOREIGN KEY FK_855DB475D32AA90E');
        $this->addSql('DROP INDEX IDX_855DB475D32AA90E ON segment_filtre');
        $this->addSql('ALTER TABLE segment_filtre ADD sf_champ INT NOT NULL, DROP champ_id');
    }
}
