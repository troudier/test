<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210423074846 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE operateur (id INT AUTO_INCREMENT NOT NULL, user_creation_id INT DEFAULT NULL, user_modification_id INT DEFAULT NULL, ope_uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', ope_libelle VARCHAR(255) NOT NULL, ope_operateur VARCHAR(255) NOT NULL, chr_crea_date DATETIME NOT NULL, chr_modif_date DATETIME NOT NULL, UNIQUE INDEX UNIQ_B4B7F99D92C0F41C (ope_uuid), INDEX IDX_B4B7F99D9DE46F0F (user_creation_id), INDEX IDX_B4B7F99D58BC3DA8 (user_modification_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE operateur ADD CONSTRAINT FK_B4B7F99D9DE46F0F FOREIGN KEY (user_creation_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE operateur ADD CONSTRAINT FK_B4B7F99D58BC3DA8 FOREIGN KEY (user_modification_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE champ_requetable ADD chr_type_input INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE operateur');
        $this->addSql('ALTER TABLE champ_requetable DROP chr_type_input');
    }
}
