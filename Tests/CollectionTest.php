<?php
/**
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Tests;

use Joomla\Entity\Tests\Models\User;
use PHPUnit\Framework\TestCase;
use Joomla\Entity\Helpers\Collection;

/**
 *
 * @since  1.0
 */
class CollectionTest extends TestCase
{

	/**
	 * @covers \Joomla\Entity\Helpers\Collection::all()
	 * @return void
	 */
	public function testAll()
	{
		$collection = new Collection([1, 2, 3]);

		$this->assertEquals([1, 2, 3],
			$collection->all(),
			"Test all."
		);
	}

	/**
	 * @depends testAll
	 * @covers \Joomla\Entity\Helpers\Collection::add()
	 * @return void
	 */
	public function testAdd()
	{
		$collection = new Collection;
		$collection->add(1);

		$this->assertCount(1,
			$collection->all(),
			"Test add."
		);
	}

	/**
	 * @covers \Joomla\Entity\Helpers\Collection::find()
	 * @return void
	 */
	public function testFind()
	{
		$user1 = new User(null, ['id' => 1, 'name' => 'user1']);
		$user2 = new User(null, ['id' => 2, 'name' => 'user2']);
		$user3 = new User(null, ['id' => 2, 'name' => 'user2']);

		$collection = new Collection([$user1, $user2, $user3]);

		$result = $collection->find(1);
		$this->assertTrue(
			$result->is($user1),
			"Test find by primary key value, true."
		);

		$result = $collection->find($user1);
		$this->assertTrue($result->is($user1),
			"Test find by Model instance, true."
		);

		$result = $collection->find(4);
		$this->assertFalse($result,
			"Test find by primary key value, false."
		);
	}

	/**
	 * @covers \Joomla\Entity\Helpers\Collection::first()
	 * @return void
	 */
	public function testFirst()
	{
		$user1 = new User(null, ['id' => 1, 'name' => 'user1']);
		$user2 = new User(null, ['id' => 2, 'name' => 'user2']);
		$user3 = new User(null, ['id' => 2, 'name' => 'user2']);

		$collection = new Collection([$user1, $user2, $user3]);
		$result = $collection->first();

		$this->assertTrue($result->is($user1),
			"Test first."
		);
	}
}