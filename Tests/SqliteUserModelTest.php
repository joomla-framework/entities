<?php
/**
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Database\Tests;

use Joomla\Entity\Tests\Helpers\SqliteCase;
use Joomla\Entity\Tests\Models\User;

/**
 *
 * @since  1.0
 */
class SqliteUserModelTest extends SqliteCase
{

	/**
	 * @return void
	 */
	public function testFind()
	{
		$model = new User(self::$driver);
		$id = $model->findLast()->id;
		$user = $model->find($id);

		$this->assertNotEmpty(
			$user->getAttributes()
		);

	}

	/**
	 * @return void
	 */
	public function testFindLast()
	{
		$model = new User(self::$driver);
		$user = $model->findLast();

		$this->assertGreaterThanOrEqual(
			0,
			$user->getPrimaryKeyValue()
		);

	}

	/**
	 * @return void
	 */
	public function testInsert()
	{
		$model = new User(self::$driver);
		$user = new User(self::$driver);

		$user->email = "test@test.com";

		$params = array();
		$params['test'] = 'val';

		$user->params = $params;

		$user->save();

		$this->assertEquals(
			$model->findLast()->id,
			$user->id
		);

	}

	/**
	 * @return void
	 */
	public function testUpdate()
	{
		$model = new User(self::$driver);
		$user = $model->findLast();

		$user->resetCount = 10;

		$user->update();

		$this->assertEquals(
			$user->resetCount,
			$model->find($user->id)->resetCount
		);
	}

	/**
	 * @return void
	 */
	public function testIncrement()
	{
		$model = new User(self::$driver);
		$user = $model->findLast();

		$user->increment('resetCount');

		$this->assertEquals(
			$user->resetCount,
			$model->find($user->id)->resetCount
		);
	}
}
