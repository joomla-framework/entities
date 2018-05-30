<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Tests;

use PHPUnit\Framework\TestCase;
use Joomla\Entity\Tests\Models\User;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Class ModelTest
 * @package Joomla\Entity\Tests
 * @since 1.0
 */
class ModelTest extends TestCase
{

	/**
	 * @return void
	 */
	public function testFind()
	{
		$id = User::findLast()->id;
		$user = User::find($id);

		$this->assertNotEmpty(
			$user->getAttributes()
		);

	}

	/**
	 * @return void
	 */
	public function testFindLast()
	{
		$user = User::findLast();

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
		$user = new User;

		$user->email = "test@test.com";

		$params = array();
		$params['test'] = 'val';

		$user->params = $params;

		$user->save();

		$this->assertEquals(
			User::findLast()->id,
			$user->id
		);

	}

	/**
	 * @return void
	 */
	public function testUpdate()
	{
		$user = User::findLast();

		$user->resetCount = 10;

		$user->update();

		$this->assertEquals(
			$user->resetCount,
			User::find($user->id)->resetCount
		);
	}

	/**
	 * @return void
	 */
	public function testIncrement()
	{
		$user = User::findLast();

		$user->increment('resetCount');

		$this->assertEquals(
			$user->resetCount,
			User::find($user->id)->resetCount
		);
	}

}

