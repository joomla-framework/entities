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

/**
 * Provides a basic interface and functionality for executing database
 * operations against a connection using a specific dataSet.
 */
interface Operation
{
    /**
     * Executes the database operation against the given $connection for the
     * given $dataSet.
     *
     * @param Connection $connection
     * @param IDataSet   $dataSet
     *
     * @throws Exception
     */
    public function execute(Connection $connection, IDataSet $dataSet);
}
