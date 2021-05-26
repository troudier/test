<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210324154533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE memo (id INT AUTO_INCREMENT NOT NULL, lien_id INT DEFAULT NULL, user_creation_id INT DEFAULT NULL, user_modification_id INT DEFAULT NULL, mem_uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', mem_texte LONGTEXT NOT NULL, mail_crea_date DATETIME NOT NULL, mail_modif_date DATETIME NOT NULL, UNIQUE INDEX UNIQ_AB4A902A950C265 (mem_uuid), INDEX IDX_AB4A902AEDAAC352 (lien_id), INDEX IDX_AB4A902A9DE46F0F (user_creation_id), INDEX IDX_AB4A902A58BC3DA8 (user_modification_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE memo ADD CONSTRAINT FK_AB4A902AEDAAC352 FOREIGN KEY (lien_id) REFERENCES personne_lien (id)');
        $this->addSql('ALTER TABLE memo ADD CONSTRAINT FK_AB4A902A9DE46F0F FOREIGN KEY (user_creation_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE memo ADD CONSTRAINT FK_AB4A902A58BC3DA8 FOREIGN KEY (user_modification_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE personne_lien ADD qualite INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE memo');
        $this->addSql('ALTER TABLE personne_lien DROP qualite');
    }
}
