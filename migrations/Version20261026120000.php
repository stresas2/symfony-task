<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261026120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create shift and shift_notification tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE shift (
            id VARCHAR(36) NOT NULL,
            employee_id VARCHAR(255) NOT NULL,
            start_at DATETIME NOT NULL,
            end_at DATETIME NOT NULL,
            country_code VARCHAR(2) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_shift_employee_start_end ON shift (employee_id, start_at, end_at)');

        $this->addSql('CREATE TABLE shift_notification (
            id VARCHAR(36) NOT NULL,
            employee_id VARCHAR(255) NOT NULL,
            data CLOB NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_shift_notification_employee_id ON shift_notification (employee_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE shift_notification');
        $this->addSql('DROP TABLE shift');
    }
}
