<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210401082923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE indicatif_telephone (id INT AUTO_INCREMENT NOT NULL, indtel_uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', indtel_pays VARCHAR(255) NOT NULL, indtel_indicatif VARCHAR(5) NOT NULL, indtel_message_validation LONGTEXT DEFAULT NULL, indtel_code_pays VARCHAR(2) NOT NULL, UNIQUE INDEX UNIQ_ABA983814A651993 (indtel_uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE telephone ADD indicatif_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE telephone ADD CONSTRAINT FK_450FF0101439B022 FOREIGN KEY (indicatif_id) REFERENCES indicatif_telephone (id)');
        $this->addSql('CREATE INDEX IDX_450FF0101439B022 ON telephone (indicatif_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telephone DROP FOREIGN KEY FK_450FF0101439B022');
        $this->addSql('DROP TABLE indicatif_telephone');
        $this->addSql('DROP INDEX IDX_450FF0101439B022 ON telephone');
        $this->addSql('ALTER TABLE telephone DROP indicatif_id');
    }
}
