<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231020190738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Generated migration for User entity";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE SEQUENCE user_id_seq INCREMENT BY 1 MINVALUE 1 START 1");
        $this->addSql("CREATE TABLE users (id INT NOT NULL PRIMARY KEY, name VARCHAR(64) DEFAULT NULL, surname VARCHAR(64) DEFAULT NULL, email VARCHAR(64) DEFAULT NULL)");
        $this->addSql("ALTER TABLE users ALTER id SET DEFAULT nextval('user_id_seq')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE users");
        $this->addSql("DROP SEQUENCE user_id_seq");
    }
}
