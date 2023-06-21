<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230621040919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD stripe_event_id VARCHAR(255) DEFAULT NULL, ADD stripe_price_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket ADD day VARCHAR(255) DEFAULT NULL, ADD hour VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP stripe_event_id, DROP stripe_price_id');
        $this->addSql('ALTER TABLE `ticket` DROP day, DROP hour');
    }
}
