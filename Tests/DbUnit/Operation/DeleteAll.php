<?php

/*
 * This file is part of DbUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Joomla\Entity\Tests\DbUnit\Operation;

use Joomla\Entity\Tests\DbUnit\Database\Connection;
use Joomla\Entity\Tests\DbUnit\DataSet\IDataSet;
use Joomla\Entity\Tests\DbUnit\DataSet\ITable;
use PDOException;

/**
 * Deletes all rows from all tables in a dataset.
 */
class DeleteAll implements Operation
{
    public function execute(Connection $connection, IDataSet $dataSet): void
    {
        foreach ($dataSet->getReverseIterator() as $table) {
            /* @var $table ITable */

            $query = "
                DELETE FROM {$connection->quoteSchemaObject($table->getTableMetaData()->getTableName())}
            ";

            try {
                $connection->getConnection()->query($query);
            } catch (PDOException $e) {
                throw new Exception('DELETE_ALL', $query, [], $table, $e->getMessage());
            }
        }
    }
}
