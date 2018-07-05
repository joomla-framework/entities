<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
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
class UserProfile extends Model
{
	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var boolean
	 */
	public $timestamps = false;

	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'user_id';

	/**
	 * Get the profile for the current user.
	 * @return Relation
	 */
	public function user()
	{
		return $this->belongsTo('Joomla\Entity\Tests\Models\User', 'user');
	}
}
