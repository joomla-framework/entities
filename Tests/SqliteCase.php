<?php

/**
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Tests;

use Joomla\Database\DatabaseFactory;
use Joomla\Database\Sqlite\SqliteDriver;

/**
 * Abstract test case class for SQLite database testing.
 * @since 1.0
 */
abstract class SqliteCase extends AbstractDatabaseTestCase
{
    /**
     * The database driver options for the connection.
     *
     * @var  array
     */
    protected static $options = ['driver' => 'sqlite', 'database' => ':memory:'];

    /**
     * This method is called before the first test of this test class is run.
     *
     * An example DSN would be: host=localhost;port=5432;dbname=joomla_ut;user=utuser;pass=ut1234
     *
     * @return  void
     */
    public static function setUpBeforeClass(): void
    {
        // Make sure the driver is supported
        if (!SqliteDriver::isSupported()) {
            static::markTestSkipped('The SQLite driver is not supported on this platform.');
        }

        try {
            // Attempt to instantiate the driver.
            $dbFactory      = new DatabaseFactory();
            static::$driver = $dbFactory->getDriver(static::$options['driver'], static::$options);

            // Get the PDO instance for an SQLite memory database and load the test schema into it.
            static::$driver->connect();
            static::$driver->getConnection()->exec(file_get_contents(__DIR__ . '/Stubs/ddl.sql'));
        } catch (\RuntimeException $e) {
            static::$driver = null;
        }

        // If for some reason an exception object was returned set our database object to null.
        if (static::$driver instanceof \Exception) {
            static::$driver = null;
        }

        static::$dataSets = [
            'banners'       => __DIR__ . '/Stubs/banners.csv',
            'messages'      => __DIR__ . '/Stubs/messages.csv',
            'users'         => __DIR__ . '/Stubs/users.csv',
            'user_profiles' => __DIR__ . '/Stubs/user_profiles.csv',
        ];
    }
}
