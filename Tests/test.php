<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @package Joomla\Entity\Tests
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

require __DIR__ . '/../vendor/autoload.php';

use Joomla\Entity\Tests\User;

$user = User::find(132);

$user->resetCount = 10;

$user->update();

$user = new User;

$user->email = "test@test.com";

$user->save();

// $key = $user->getPrimaryKey() - 1;

// User::find($key)->delete();

