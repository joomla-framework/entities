<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Tests;
use Joomla\Entity\Tests\Models\User;

/**
 * @todo add columns to tests with selection where possible
 *
 * @since  1.0
 */
class QueryTest extends SqliteCase
{
	/**
	 * @covers \Joomla\Entity\Query::find()
	 * @return void
	 */
	public function testFind()
	{
		$model = new User(self::$driver);
		$user = $model->find(42);
		$user2 = $model->find(420);

		$this->assertNotEmpty(
			$user->getAttributes()
		);

		$this->assertFalse(
			$user2
		);
	}

	/**
	 * @covers \Joomla\Entity\Query::findLast()
	 * @return void
	 */
	public function testFindLast()
	{
		$model = new User(self::$driver);
		$user = $model->findLast();

		$this->assertEquals(
			100,
			$user->getPrimaryKeyValue()
		);

	}

	/**
	 * @covers \Joomla\Entity\Query::first()
	 * @return void
	 */
	public function testFirst()
	{
		$model = new User(self::$driver);
		$user = $model->first();

		$this->assertEquals(
			42,
			$user->getPrimaryKeyValue()
		);

	}

	/**
	 * @covers \Joomla\Entity\Query::get()
	 * @return void
	 */
	public function testGet()
	{
		$model = new User(self::$driver);
		$users = $model->get();

		$this->assertCount(
			5,
			$users
		);
	}

	/**
	 * @covers \Joomla\Entity\Query::count()
	 * @return void
	 */
	public function testCount()
	{
		$model = new User(self::$driver);
		$count = $model->count();

		$this->assertEquals(
			5,
			$count
		);
	}
}
