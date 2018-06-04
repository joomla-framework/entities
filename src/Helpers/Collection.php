<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Helpers;

use ArrayAccess;
use JsonSerializable;
use IteratorAggregate;
use ArrayIterator;


/**
 * Class Collection
 * @package Joomla\Entity\Helpers
 * @since   1.0
 */
class Collection implements ArrayAccess, IteratorAggregate, JsonSerializable
{
	/**
	 * The items contained in the collection.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * Get an iterator for the items.
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->items);
	}

	/**
	 * Determine if an item exists at an offset.
	 *
	 * @param   mixed  $offset key position in array
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->items);
	}

	/**
	 * Get an item at a given offset.
	 *
	 * @param   mixed  $offset key position in array
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->items[$offset];
	}

	/**
	 * Set the item at a given offset.
	 *
	 * @param   mixed  $offset key position in array
	 * @param   mixed  $value  value to be set
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		if (is_null($offset))
		{
			$this->items[] = $value;
		}
		else
		{
			$this->items[$offset] = $value;
		}
	}

	/**
	 * Unset the item at a given offset.
	 *
	 * @param   mixed  $offset key position in array
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->items[$offset]);
	}

	/**
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		return array_map(
			function ($value)
			{
				return $value->jsonSerialize();
			},
			$this->items
		);
	}


}