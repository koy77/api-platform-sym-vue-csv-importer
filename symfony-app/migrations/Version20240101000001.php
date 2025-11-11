<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the tblProductData table and adds stock and price columns
 */
final class Version20240101000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates tblProductData table and adds stock and price columns';
    }

    public function up(Schema $schema): void
    {
        // Create table if it doesn't exist
        $this->addSql('
            CREATE TABLE IF NOT EXISTS tblProductData (
                intProductDataId INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                strProductName VARCHAR(50) NOT NULL,
                strProductDesc VARCHAR(255) NOT NULL,
                strProductCode VARCHAR(10) NOT NULL,
                dtmAdded DATETIME DEFAULT NULL,
                dtmDiscontinued DATETIME DEFAULT NULL,
                stmTimestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                stock INT(11) DEFAULT 0,
                price DECIMAL(10,2) DEFAULT 0.00,
                PRIMARY KEY (intProductDataId),
                UNIQUE KEY (strProductCode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT=\'Stores product data\'
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS tblProductData');
    }
}

