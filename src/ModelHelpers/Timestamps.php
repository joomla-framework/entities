<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\ModelHelpers;

use Carbon\Carbon;

/**
 * Trait Timestamps
 * @package Joomla\Entity\Helpers
 * @since 1.0
 */
trait Timestamps
{
	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var boolean
	 */
	public $timestamps = true;

	/**
	 * Update the model's update timestamp.
	 *
	 * @return boolean
	 */
	public function touch()
	{
		if (! $this->usesTimestamps())
		{
			return false;
		}

		$this->updateTimestamps();

		return $this->save();
	}

	/**
	 * Update the creation and update timestamps.
	 *
	 * @return void
	 */
	protected function updateTimestamps()
	{
		$time = $this->freshTimestamp();
		$updatedAt = $this->getColumnAlias('updated_at');
		$createdAt = $this->getColumnAlias('updated_at');

		if (! is_null($updatedAt) && ! $this->isDirty($updatedAt))
		{
			$this->setUpdatedAt($time);
		}

		if (! $this->exists && ! is_null($createdAt) && ! $this->isDirty($createdAt))
		{
			$this->setCreatedAt($time);
		}
	}

	/**
	 * Set the value of the "created at" attribute.
	 *
	 * @param   mixed  $value created_at value
	 * @return $this
	 */
	public function setCreatedAt($value)
	{
		$this->createdAt = $value;

		return $this;
	}

	/**
	 * Set the value of the "updated at" attribute.
	 *
	 * @param   mixed  $value updated_at value
	 * @return $this
	 */
	public function setUpdatedAt($value)
	{
		$this->updateAt = $value;

		return $this;
	}

	/**
	 * Get a fresh timestamp for the model.
	 *
	 * @return \Carbon\Carbon
	 */
	public function freshTimestamp()
	{
		return new Carbon;
	}

	/**
	 * Get a fresh timestamp for the model.
	 *
	 * @return string
	 */
	public function freshTimestampString()
	{
		return $this->fromDateTime($this->freshTimestamp());
	}

	/**
	 * Determine if the model uses timestamps.
	 *
	 * @return boolean
	 */
	public function usesTimestamps()
	{
		return $this->timestamps;
	}

	/**
	 * Get the name of the "created at" column.
	 *
	 * @return string
	 */
	public function getCreatedAtColumn()
	{
		return $this->createdAt;
	}

	/**
	 * Get the name of the "updated at" column.
	 *
	 * @return string
	 */
	public function getUpdatedAtColumn()
	{
		return $this->updatedAt;
	}
}
