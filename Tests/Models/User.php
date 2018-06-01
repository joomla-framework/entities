<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */


namespace Joomla\Entity\Tests\Models;

use Joomla\Entity\Model;
use PHPUnit\DbUnit\DataSet\CsvDataSet;

/**
 * Class User
 * @package Joomla\Entity\Tests
 * @since 1.0
 */
class User extends Model
{
	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array
	 */
	protected $casts = array(
		'params' => 'array'
	);

	/**
	 * Data Sets used for testing. Each item must be defined by its full path to the CSV file.
	 *
	 * @var array
	 */
	public $dataSets = array(
		__DIR__ . '/../Stubs/users.csv'
	);

}