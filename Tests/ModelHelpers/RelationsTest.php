<?php

/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Tests\ModelHelpers;

use Joomla\Entity\Model;
use Joomla\Entity\Tests\Models\Message;
use Joomla\Entity\Tests\Models\User;
use Joomla\Entity\Tests\SqliteCase;

/**
 * @since  1.0
 */
class RelationsTest extends SqliteCase
{
    /**
     *
     * @covers \Joomla\Entity\Model::with()
     * @covers \Joomla\Entity\Query::createSelectWithConstraint()
     * @return void
     */
    public function testEagerLoad()
    {
        $userModel = new User(self::$driver);

        $user = $userModel->with('receivedMessages')->find(42);

        $this->assertArrayHasKey(
            'receivedMessages',
            $user->getRelations()
        );

        $this->assertInstanceOf(
            Model::class,
            $user->getRelations()['receivedMessages']->first()
        );

        $user = $userModel->find(42, ['id']);

        $message = $user->sentMessages->first();

        $messageModel = new Message(self::$driver);
        $messageCheck = $messageModel->find(1, ['message_id', 'subject']);

        $this->assertTrue(
            $message->is($messageCheck)
        );
    }

    /**
     * @covers \Joomla\Entity\Model::eagerLoad()
     * @return void
     */
    public function testLoadRelations()
    {
        $userModel = new User(self::$driver);

        $user = $userModel->find(42);

        $this->assertArrayNotHasKey(
            'receivedMessages',
            $user->getRelations()
        );

        $user->eagerLoad('receivedMessages');

        $this->assertArrayHasKey(
            'receivedMessages',
            $user->getRelations()
        );
    }

    /**
     * @covers \Joomla\Entity\Model::hasMany()
     * @covers \Joomla\Entity\Model::with
     * @return void
     */
    public function testOneToManyEager()
    {
        $userModel = new User(self::$driver);

        $user = $userModel->find(42);

        $sentMessages = $user->getRelations()['sentMessages'];

        $this->assertCount(1, $sentMessages);
    }
}
