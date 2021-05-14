<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210422150002 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personne_lien ADD fonction_personnalisee VARCHAR(30) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personne_lien ADD fonction_personnalisee_id INT DEFAULT NULL, DROP fonction_personnalisee');
        $this->addSql('ALTER TABLE personne_lien ADD CONSTRAINT FK_457F92A93A551A69 FOREIGN KEY (fonction_personnalisee_id) REFERENCES personne_lien_fonction (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_457F92A93A551A69 ON personne_lien (fonction_personnalisee_id)');
    }
}
