<?php
/**
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Tests;

use Joomla\Entity\Tests\Helpers\SqliteCase;
use Joomla\Entity\Tests\Models\Banner;
use Joomla\Entity\Tests\Models\Message;
use Joomla\Entity\Tests\Models\User;
use Joomla\Entity\Tests\Models\UserProfile;
use Joomla\Entity\Model;

/**
 *
 * @since  1.0
 */
class EntityTest extends SqliteCase
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
	 * @covers \Joomla\Entity\Model::save()
	 * @covers \Joomla\Entity\Model::performInsert()
	 * @covers \Joomla\Entity\Query::insert()
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
	 * @covers \Joomla\Entity\Model::update()
	 * @covers \Joomla\Entity\Model::save()
	 * @covers \Joomla\Entity\Model::performUpdate()
	 * @covers \Joomla\Entity\Query::update()
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
	 * @covers \Joomla\Entity\Model::delete()
	 * @return void
	 */
	public function testDelete()
	{
		$model = new User(self::$driver);

		$model->delete(100);

		$this->assertFalse(
			$model->find(100)
		);
	}

	/**
	 * @covers \Joomla\Entity\Model::increment()
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
	 * @covers \Joomla\Entity\Model::touch()
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
	 * @covers \Joomla\Entity\Model::hasOne()
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
	 * @covers \Joomla\Entity\Model::hasMany()
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
	 * @covers \Joomla\Entity\Model::hasMany()
	 * @covers \Joomla\Entity\Model::$with
	 * @return void
	 */
	public function testOneToManyEager()
	{
		$userModel = new User(self::$driver);

		$user = $userModel->find(42);

		$sentMessages = $user->getRelations()['sentMessages'];

		$this->assertCount(1, $sentMessages);
	}

	/**
	 * @covers Model::getPrimaryKey()
	 * @return void
	 */
	public function testGetPrimaryKey()
	{
		$userModel = new User(self::$driver);

		$this->assertEquals('id', $userModel->getPrimaryKey());
	}

	/**
	 * * @covers Model::getPrimaryKeyValue()
	 * @return void
	 */
	public function testGetPrimaryKeyValue()
	{
		$userModel = new User(self::$driver);
		$user = $userModel->find(42);

		$this->assertEquals(42, $user->getPrimaryKeyValue());
	}
}
