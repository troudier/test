<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210421121946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE champ_requetable (id INT AUTO_INCREMENT NOT NULL, user_creation_id INT DEFAULT NULL, user_modification_id INT DEFAULT NULL, chr_uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', chr_libelle VARCHAR(255) NOT NULL, chr_champ_bd VARCHAR(255) NOT NULL, chr_type INT NOT NULL, chr_crea_date DATETIME NOT NULL, chr_modif_date DATETIME NOT NULL, UNIQUE INDEX UNIQ_B4549E538F8D01D9 (chr_uuid), INDEX IDX_B4549E539DE46F0F (user_creation_id), INDEX IDX_B4549E5358BC3DA8 (user_modification_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE champ_requetable ADD CONSTRAINT FK_B4549E539DE46F0F FOREIGN KEY (user_creation_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE champ_requetable ADD CONSTRAINT FK_B4549E5358BC3DA8 FOREIGN KEY (user_modification_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE champ_requetable');
    }
}
