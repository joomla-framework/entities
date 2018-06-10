<?php
/**
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Tests;

use Joomla\Entity\Tests\Helpers\SqliteCase;
use Joomla\Entity\Tests\Models\Banner;
use Joomla\Entity\Tests\Models\Message;
use Joomla\Entity\Tests\Models\User;
use Joomla\Entity\Tests\Models\UserProfile;

/**
 *
 * @since  1.0
 */
class SqliteUserModelTest extends SqliteCase
{

	/**
	 * This method is called before the first test of this test class is run.
	 * @return void
	 */
	public static function setUpBeforeClass()
	{
		static::$dataSets = array(
			'banners'       => __DIR__ . '/Stubs/banners.csv',
			'messages'      => __DIR__ . '/Stubs/messages.csv',
			'users'         => __DIR__ . '/Stubs/users.csv',
			'user_profiles' => __DIR__ . '/Stubs/user_profiles.csv'
			);

		parent::setUpBeforeClass();
	}

	/**
	 * @return void
	 */
	public function testFind()
	{
		$model = new User(self::$driver);
		$user = $model->find(42);

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

		$this->assertEquals(
			100,
			$user->getPrimaryKeyValue()
		);

	}

	/**
	 * @return void
	 */
	public function testInsert()
	{
		$user = new User(self::$driver);

		$user->email = "test@test.com";

		$params = array();
		$params['test'] = 'val';

		$user->params = $params;

		$user->save();

		$this->assertEquals(
			101,
			$user->id
		);

	}

	/**
	 * @return void
	 */
	public function testUpdate()
	{
		$model = new User(self::$driver);

		$user = $model->find(100);
		$user->resetCount = 10;

		$user->update();

		$this->assertEquals(
			10,
			$model->find(100)->resetCount
		);
	}

	/**
	 * @return void
	 */
	public function testIncrement()
	{
		$model = new User(self::$driver);
		$user = $model->find(42);

		$user->increment('resetCount');

		$this->assertEquals(
			1,
			$model->find(42)->resetCount
		);
	}

	/**
	 * @return void
	 */
	public function testTouch()
	{
		$model = new Banner(self::$driver);
		$banner = $model->find(4);

		$banner->touch();

		$this->assertEquals(
			$banner->updatedAt,
			$model->find(4)->updatedAt
		);
	}

	/**
	 * @return void
	 */
	public function testOneToOne()
	{
		$userModel = new User(self::$driver);
		$userProfileModel = new UserProfile(self::$driver);

		$user = $userModel->find(42);
		$userProfile = $userProfileModel->find(42);

		$this->assertTrue($userProfile->is($user->profile));
	}

	/**
	 * @return void
	 */
	public function testOneToMany()
	{
		$userModel = new User(self::$driver);

		$user = $userModel->find(42);

		$messages = $user->receivedMessages;

		$this->assertCount(4, $messages);
	}

	/**
	 * @return void
	 */
	public function testOneToManyEager()
	{
		$userModel = new User(self::$driver);

		$user = $userModel->find(42);

		$sentMessages = $user->getRelations()['sentMessages'];

		$this->assertCount(1, $sentMessages);
	}

	// TODO getPrimaryKey, getPrimaryKeyValue
}
