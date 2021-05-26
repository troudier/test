<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210402124346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personne_physique ADD apporteur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE personne_physique ADD CONSTRAINT FK_5C2B29A284FC98A0 FOREIGN KEY (apporteur_id) REFERENCES personne_physique (id)');
        $this->addSql('CREATE INDEX IDX_5C2B29A284FC98A0 ON personne_physique (apporteur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personne_physique DROP FOREIGN KEY FK_5C2B29A284FC98A0');
        $this->addSql('DROP INDEX IDX_5C2B29A284FC98A0 ON personne_physique');
        $this->addSql('ALTER TABLE personne_physique DROP apporteur_id');
    }
}
