<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Tests\ModelHelpers;

use Joomla\Entity\Tests\Models\Banner;
use Joomla\Entity\Tests\Models\User;
use Joomla\Entity\Tests\SqliteCase;

/**
 * @since  1.0
 */
class SerializationTest extends SqliteCase
{
    /**
     * @covers \Joomla\Entity\ModelHelpers\Serialization::toArray()
     * @return void
     */
    public function testToArray()
    {
        /**
         * @improvement add cases (optional):
         * - model with nested relations
         */

        $model  = new Banner(self::$driver);
        $banner = $model->find(4, ['id', 'createdAt']);

        $expected = ['id' => '4', 'created' => '2011-01-01 00:00:01'];
        $this->assertEquals(
            $expected,
            $banner->toArray()
        );

        $userModel = new User(self::$driver);
        $user      = $userModel->find(42, ['id']);
        $userArray = $user->toArray();

        $expected = [
            "id"           => 42,
            "sentMessages" => [
                0 => [
                    "message_id"   => 1,
                    "subject"      => "message1",
                    "user_id_from" => "42",
                ],
            ],
        ];

        $this->assertEquals(
            $expected,
            $userArray
        );

        $relationsArray = $user->getRelationsAsArray();

        $this->assertEquals(
            $expected["sentMessages"],
            $relationsArray["sentMessages"]
        );
    }

    /**
     * @covers \Joomla\Entity\ModelHelpers\Serialization::jsonSerialize()
     * @covers \Joomla\Entity\ModelHelpers\Serialization::toJson()
     * @return void
     */
    public function testJsonSerialize()
    {
        $model = new User(self::$driver);
        $user  = $model->find(42, ['id', 'username', 'password']);

        $expected = json_encode([
            "id"           => 42,
            "username"     => "admin",
            "sentMessages" => [
                [
                    "message_id"   => 1,
                    "subject"      => "message1",
                    "user_id_from" => PHP_VERSION_ID < '80100' ? "42" : 42,
                ],
            ],
        ]);

        $this->assertEquals(
            $expected,
            $user->toJson()
        );

        $user->addHidden('sentMessages');

        $expected = '{"id":42,"username":"admin"}';

        $this->assertEquals(
            $expected,
            $user->toJson()
        );
    }
}
