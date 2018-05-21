<?php
/**
 * Created by PhpStorm.
 * User: isac
 * Date: 20/05/2018
 * Time: 9:14 PM
 */

require __DIR__ . '/../vendor/autoload.php';

use Joomla\Entity\Tests\User;

$user = User::find(132);

$user->resetCount = 10;

$user->update();

$user = new User;

$user->email = "test@test.com";

$user->save();

//$key = $user->getPrimaryKey() - 1;

//User::find($key)->delete();

