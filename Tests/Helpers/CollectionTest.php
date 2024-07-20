<?php

/**
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Tests\Helpers;

use Joomla\Entity\Helpers\Collection;
use Joomla\Entity\Tests\Models\User;
use Joomla\Entity\Tests\Models\UserProfile;
use Joomla\Entity\Tests\SqliteCase;

/**
 *
 * @since  1.0
 */
class CollectionTest extends SqliteCase
{
    /**
     * @covers \Joomla\Entity\Helpers\Collection::all()
     * @return void
     */
    public function testAll()
    {
        $collection = new Collection([1, 2, 3]);

        $this->assertEquals(
            [1, 2, 3],
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
        $collection = new Collection();
        $collection->add(1);

        $this->assertCount(
            1,
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
        $user1 = new User(self::$driver, ['id' => 1, 'name' => 'user1']);
        $user2 = new User(self::$driver, ['id' => 2, 'name' => 'user2']);
        $user3 = new User(self::$driver, ['id' => 3, 'name' => 'user3']);

        $collection = new Collection([$user1, $user2, $user3]);

        $result = $collection->find(1);
        $this->assertTrue(
            $result->is($user1),
            "Test find by primary key value, true."
        );

        $result = $collection->find($user1);
        $this->assertTrue(
            $result->is($user1),
            "Test find by Model instance, true."
        );

        $result = $collection->find(4);
        $this->assertFalse(
            $result,
            "Test find by primary key value, false."
        );
    }

    /**
     * @covers \Joomla\Entity\Helpers\Collection::first()
     * @return void
     */
    public function testFirst()
    {
        $user1 = new User(self::$driver, ['id' => 1, 'name' => 'user1']);
        $user2 = new User(self::$driver, ['id' => 2, 'name' => 'user2']);
        $user3 = new User(self::$driver, ['id' => 2, 'name' => 'user2']);

        $collection = new Collection([$user1, $user2, $user3]);
        $result     = $collection->first();

        $this->assertTrue(
            $result->is($user1),
            "Test first."
        );
    }

    /**
     * @covers \Joomla\Entity\Helpers\Collection::sort()
     * @return void
     */
    public function testSort()
    {
        $userProfileModel = new UserProfile(self::$driver);

        $items = $userProfileModel->get();

        $items = $items->sort(
            function ($a, $b) {
                if ($a->user->name == $b->user->name) {
                    return 0;
                }

                return ($a->user->name < $b->user->name) ? -1 : 1;
            }
        );

        $ids = array_map(
            function ($item) {
                return $item['user_id'];
            },
            $items->all()
        );

        $this->assertEquals(
            [100, 44 ,43 ,42 ,99],
            $ids
        );
    }


    /**
     * @covers \Joomla\Entity\Helpers\Collection::sortByOrdering()
     * @return void
     */
    public function testSortByOrdering()
    {
        $userProfileModel = new UserProfile(self::$driver);

        $items = $userProfileModel->get();

        $items = $items->sortByOrdering('user.name DESC');

        $ids = array_map(
            function ($item) {
                return $item['user_id'];
            },
            $items->all()
        );

        $this->assertEquals(
            [99, 42, 43, 44, 100],
            $ids
        );
    }
}
