<?php
/**
 * Created by PhpStorm.
 * User: isac
 * Date: 20/05/2018
 * Time: 9:06 PM
 */


namespace Joomla\Entity\Tests;

class User extends \Joomla\Entity\Model
{
	protected $table = '#__users';

	protected $defaultParams = [
		'params' => ''
		];
}