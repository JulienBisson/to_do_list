<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231010140610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_50586597A76ED395');
        $this->addSql('DROP TABLE users');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_8D93D649E7927C74 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
    
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT FK_8D93D649E7927C74 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tasks ADD FOREIGN KEY FK_50586597A76ED395');
        

    }
}
