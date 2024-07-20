<?php

/**
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Tests\ModelHelpers;

use Joomla\Entity\Exceptions\AttributeNotFoundException;
use Joomla\Entity\Helpers\Collection;
use Joomla\Entity\Model;
use Joomla\Entity\Tests\Models\User;
use Joomla\Entity\Tests\SqliteCase;

/**
 * @since  1.0
 */
class AttributesTest extends SqliteCase
{
    /**
     * This method is called before the first test of this test class is run.
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        static::$dataSets = [
            'banners'       => __DIR__ . '/Stubs/banners.csv',
            'messages'      => __DIR__ . '/Stubs/messages.csv',
            'users'         => __DIR__ . '/Stubs/users.csv',
            'user_profiles' => __DIR__ . '/Stubs/user_profiles.csv',
        ];

        parent::setUpBeforeClass();
    }

    /**
     * @covers \Joomla\Entity\ModelHelpers\Attributes::getAttribute()
     * @return void
     */
    public function testGetAttribute()
    {
        $userModel = new User(self::$driver);

        $user = $userModel->find(42);

        $simpleAttribute = $user->getAttribute('id');

        $mutatorAttribute = $user->getAttribute('newAccount');

        $dateAttribute = $user->getAttribute('registerDate');

        $castAttribute = $user->getAttribute('params');

        $relationAttribute = $user->getAttribute('sentMessages');

        $this->assertEquals(
            42,
            $simpleAttribute
        );

        $this->assertTrue(
            $mutatorAttribute
        );

        $this->assertEquals(
            '2010-02-13 00:34:42',
            $dateAttribute
        );

        $this->assertEquals(
            ['test' => 'Object'],
            $castAttribute
        );

        $this->assertInstanceOf(
            Collection::class,
            $relationAttribute
        );

        $this->assertInstanceOf(
            Model::class,
            $relationAttribute->first()
        );

        $this->assertEquals(
            1,
            $relationAttribute->first()->message_id
        );
    }

    /**
     * @covers \Joomla\Entity\ModelHelpers\Attributes::getAttributeValue()
     * @return void
     */
    public function testGetAttributeValue()
    {
        $userModel = new User(self::$driver);

        $user = $userModel->find(42);

        $simpleAttribute = $user->getAttributeValue('id');

        $mutatorAttribute = $user->getAttributeValue('newAccount');

        $dateAttribute = $user->getAttributeValue('registerDate');

        $castAttribute = $user->getAttributeValue('params');

        $this->assertEquals(
            42,
            $simpleAttribute
        );

        $this->assertTrue(
            $mutatorAttribute
        );

        $this->assertEquals(
            '2010-02-13 00:34:42',
            $dateAttribute
        );

        $this->assertEquals(
            ['test' => 'Object'],
            $castAttribute
        );
    }

    /**
     * @covers \Joomla\Entity\ModelHelpers\Attributes::getAttributeNested()
     * @return void
     */
    public function testGetNestedAttribute()
    {
        $userModel = new User(self::$driver);

        $user = $userModel->find(42);

        $this->assertEquals(
            $user->getAttributeNested('profile.profile_key'),
            $user->profile->profile_key
        );
    }

    /**
     * @covers \Joomla\Entity\ModelHelpers\Attributes::getAttribute()
     * @return void
     */
    public function testExceptionForGetAttribute()
    {
        $userModel = new User(self::$driver);

        $user = $userModel->find(42);

        $this->expectException(AttributeNotFoundException::class);
        $user->getAttribute("notExistent");
    }

    /**
     * @covers \Joomla\Entity\ModelHelpers\Attributes::getAttributeValue()
     * @return void
     */
    public function testExceptionForGetAttributeValue()
    {
        $userModel = new User(self::$driver);

        $user = $userModel->find(42);

        $this->expectException(AttributeNotFoundException::class);
        $user->getAttributeValue('sentMessages');
    }

    /**
     * @covers \Joomla\Entity\ModelHelpers\Attributes::setAttribute()
     * @return void
     */
    public function testSetAttribute()
    {
        $userModel = new User(self::$driver);

        $user = $userModel->find(42);

        // Simple Attribute
        $user->setAttribute('username', 'test');

        // Mutator Attribute
        $user->setAttribute('reset', 1);

        // Date Attribute
        $user->setAttribute('registerDate', '2010-02-13 00:34:43');

        // Cast Attribute
        $arr = ['test' => 'test'];
        $user->setAttribute('params', $arr);

        $this->expectException(AttributeNotFoundException::class);
        $user->setAttribute('notExistent', 'value');

        $this->assertEquals(
            'test',
            $user->username
        );

        $this->assertEquals(
            1,
            $user->resetCount
        );

        $this->assertEquals(
            '0000-00-00 00:00:01',
            $user->lastResetTime
        );

        $this->assertEquals(
            '2010-02-13 00:34:43',
            $user->registerDate
        );

        $this->assertEquals(
            $arr,
            $user->params
        );
    }
}
