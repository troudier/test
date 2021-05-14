<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210427083317 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE segment_filtre ADD operateur_id INT DEFAULT NULL, DROP sf_operateur');
        $this->addSql('ALTER TABLE segment_filtre ADD CONSTRAINT FK_855DB4753F192FC FOREIGN KEY (operateur_id) REFERENCES operateur (id)');
        $this->addSql('CREATE INDEX IDX_855DB4753F192FC ON segment_filtre (operateur_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE segment_filtre DROP FOREIGN KEY FK_855DB4753F192FC');
        $this->addSql('DROP INDEX IDX_855DB4753F192FC ON segment_filtre');
        $this->addSql('ALTER TABLE segment_filtre ADD sf_operateur INT NOT NULL, DROP operateur_id');
    }
}
