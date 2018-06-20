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
 * Class Message
 * @package Joomla\Entity\Tests\Models
 * @since   1.0
 */
class Message extends Model
{
	/**
	 * The attributes that should be mutated to dates. Already aliased!
	 *
	 * @var array
	 */
	protected $dates = array(
		'date_time'
	);

	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'message_id';
}
