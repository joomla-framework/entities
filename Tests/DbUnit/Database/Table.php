<?php

/*
 * This file is part of DbUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Joomla\Entity\Tests\DbUnit\Database;

use Joomla\Entity\Tests\DbUnit\DataSet\AbstractTable;
use Joomla\Entity\Tests\DbUnit\DataSet\ITableMetadata;
use PDO;

/**
 * Provides the functionality to represent a database table.
 */
class Table extends AbstractTable
{
    /**
     * Creates a new database table object.
     *
     * @param ITableMetadata $tableMetaData
     * @param Connection     $databaseConnection
     */
    public function __construct(ITableMetadata $tableMetaData, Connection $databaseConnection)
    {
        $this->setTableMetaData($tableMetaData);

        $pdoStatement = $databaseConnection->getConnection()->prepare(DataSet::buildTableSelect($tableMetaData, $databaseConnection));
        $pdoStatement->execute();
        $this->data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
    }
}
