<?php
/**
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Tests;

use Joomla\Database\DatabaseDriver;
use Joomla\Entity\Tests\DbUnit\Database\DefaultConnection;
use Joomla\Entity\Tests\DbUnit\DataSet\CsvDataSet;
use Joomla\Entity\Tests\DbUnit\Operation\Composite;
use Joomla\Entity\Tests\DbUnit\Operation\Factory;
use Joomla\Entity\Tests\DbUnit\Operation\Operation;
use Joomla\Entity\Tests\DbUnit\TestCase;

/**
 * Base test case for the database package
 *
 * @since  1.0
 */
abstract class AbstractDatabaseTestCase extends TestCase
{
    /**
     * The active database driver being used for the tests.
     *
     * @var  DatabaseDriver
     */
    protected static $driver;

    /**
     * The database driver options for the connection.
     *
     * @var  array
     */
    protected static $options = [];

    /**
     * List of data sets to be used in testing. Mapping between table and data set csv file location.
     *
     * @var array
     */
    protected static $dataSets = [];

    /**
     * Sets up the fixture.
     *
     * This method is called before a test is executed.
     *
     * @return  void
     */
    protected function setUp(): void
    {
        if (!static::$driver) {
            $this->markTestSkipped('There is no database driver.');
        }

        parent::setUp();
    }

    /**
     * This method is called after the last test of this test class is run.
     *
     * @return  void
     */
    public static function tearDownAfterClass(): void
    {
        if (static::$driver !== null) {
            static::$driver->disconnect();
            static::$driver = null;
        }
    }

    /**
     * Gets the data set to be loaded into the database during setup
     *
     * @return CsvDataSet
     */
    protected function getDataSet()
    {
        $dataSet = new CsvDataSet(',', "'", '\\');

        foreach (static::$dataSets as $table => $csv) {
            $dataSet->addTable($table, $csv);
        }

        return $dataSet;
    }

    /**
     * Returns the default database connection for running the tests.
     *
     * @return  DefaultConnection
     */
    protected function getConnection()
    {
        if (static::$driver === null) {
            static::fail('Could not fetch a database driver to establish the connection.');
        }

        static::$driver->connect();

        return $this->createDefaultDBConnection(static::$driver->getConnection(), static::$options['database']);
    }

    /**
     * Returns the database operation executed in test setup.
     *
     * @return  Operation
     */
    protected function getSetUpOperation()
    {
        // Required given the use of InnoDB constraints.
        return new Composite(
            [
                Factory::DELETE_ALL(),
                Factory::INSERT(),
            ]
        );
    }

    /**
     * Returns the database operation executed in test cleanup.
     *
     * @return  Operation
     */
    protected function getTearDownOperation()
    {
        // Required given the use of InnoDB constraints.
        return Factory::DELETE_ALL();
    }
}
