<?php
/**
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Tests;

use Joomla\Entity\Query;
use Joomla\Entity\Tests\Models\Banner;
use Joomla\Entity\Tests\Models\Message;
use Joomla\Entity\Tests\Models\User;
use Joomla\Entity\Tests\Models\UserProfile;
use Joomla\Entity\Model;

/**
 * @since  1.0
 */
class ModelTest extends SqliteCase
{
	/**
	 * @covers \Joomla\Entity\Model::save()
	 * @covers \Joomla\Entity\Model::performInsert()
	 * @covers \Joomla\Entity\Query::insert()
	 * @return void
	 */
	public function testInsert()
	{
		$attributes = [
			'email' => "test@test.com",
			'params' => ['test' => 'val']
			];

		$user = new User(self::$driver, $attributes);

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
	 * NOT dependent on the DatabaseDriver
	 *
	 * @covers Model::getPrimaryKey()
	 * @return void
	 */
	public function testGetPrimaryKey()
	{
		$userModel = new User(self::$driver);

		$this->assertEquals('id', $userModel->getPrimaryKey());
	}

	/**
	 * NOT dependent on the DatabaseDriver
	 *
	 * @covers Model::getPrimaryKeyValue()
	 * @return void
	 */
	public function testGetPrimaryKeyValue()
	{
		$user = new User(self::$driver);
		$user->setAttribute('id', 42);

		$this->assertEquals(42, $user->getPrimaryKeyValue());
	}

	/**
	 * NOT dependent on the DatabaseDriver
	 *
	 * @covers Model::is()
	 * @return void
	 */
	public function testIs()
	{
		$attributes = ['id' => 42, 'name' => 'myname'];

		$user1 = new User(self::$driver, $attributes);
		$user2 = new User(self::$driver, $attributes);

		$this->assertTrue($user1->is($user2));
	}

	/**
	 * NOT dependent on the DatabaseDriver
	 *
	 * @covers Model::getColumnAlias()
	 * @return void
	 */
	public function testGetColumnAlias()
	{
		$banner = new Banner(self::$driver);

		$this->assertEquals(
			$banner->getColumnAlias('createdAt'),
			'created'
		);

		$this->assertEquals(
			$banner->getColumnAlias('randomColumn'),
			'randomColumn'
		);
	}

	/**
	 * NOT dependent on the DatabaseDriver
	 *
	 * @covers Model::getQualifiedPrimaryKey()
	 * @return void
	 */
	public function testGetQualifiedPrimaryKey()
	{
		$user = new User(self::$driver);

		$this->assertEquals(
			$user->getQualifiedPrimaryKey(),
			'#__users.id'
		);
	}

	/**
	 * NOT dependent on the DatabaseDriver
	 *
	 * @covers Model::qualifyColumn()
	 * @return void
	 */
	public function testQualifyColumns()
	{
		$user = new User(self::$driver);

		$this->assertEquals(
			$user->qualifyColumn('id'),
			'#__users.id'
		);

		$this->assertEquals(
			$user->qualifyColumn('#__table_alias.id'),
			'#__table_alias.id'
		);

		$this->assertEquals(
			$user->qualifyColumn('table_alias.id'),
			'#__table_alias.id'
		);
	}

	/**
	 * NOT dependent on the DatabaseDriver
	 *
	 * @covers Model::newQuery()
	 * @return void
	 */
	public function testNewQuery()
	{
		$user = new User(self::$driver);

		$query = $user->newQuery();

		$this->assertInstanceOf(
			Query::class,
			$query
		);
	}
}
