<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */


namespace Joomla\Entity\Tests\Models;

use Joomla\Entity\Model;

/**
 * Class Banner
 * @package Joomla\Entity\Tests
 * @since 1.0
 */
class Banner extends Model
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
	 * The attributes that should be mutated to dates. Already aliased!
	 *
	 * @var array
	 */
	protected $dates = array(
		'checked_out_time',
		'publish_up',
		'publish_down',
		'reset',
		'created',
		'language',
		'created_by',
		'created_by_alias',
		'modified'
	);

	/**
	 * Array with alias for "special" columns such as ordering, hits etc etc
	 *
	 * @var    array
	 */
	protected $columnAlias = array(
		'createdAt' => 'created',
		'updatedAt' => 'modified'
	);

}