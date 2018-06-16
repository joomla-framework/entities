<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Tests\Models;

use Joomla\Entity\Model;
use Joomla\Entity\Relations\Relation;

/**
 * Class User
 * @package Joomla\Entity\Tests
 * @since 1.0
 */
class User extends Model
{
	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var boolean
	 */
	public $timestamps = false;

	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array
	 */
	protected $casts = array(
		'params' => 'array'
	);

	/**
	 * The relations to eager load on every query.
	 *
	 * @var array
	 * @todo add to docs: primary key and foreign key are mandatory!!!
	 */
	protected $with = array(
		'sentMessages:message_id,subject,user_id_from'
	);

	/**
	 * Get the profile for the current user.
	 * @return Relation
	 */
	public function profile()
	{
		return $this->hasOne('Joomla\Entity\Tests\Models\UserProfile');
	}

	/**
	 * Get the sent messages for the current user.
	 * @return Relation
	 */
	public function sentMessages()
	{
		return $this->hasMany('Joomla\Entity\Tests\Models\Message', 'user_id_from');
	}

	/**
	 * Get the received messages  for the current user.
	 * @return Relation
	 */
	public function receivedMessages()
	{
		return $this->hasMany('Joomla\Entity\Tests\Models\Message', 'user_id_to');
	}
}
