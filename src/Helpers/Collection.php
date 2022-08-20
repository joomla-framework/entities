<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Helpers;

use ArrayAccess;
use Closure;
use Countable;
use Joomla\Entity\Exceptions\JsonEncodingException;
use JsonSerializable;
use IteratorAggregate;
use ArrayIterator;
use Joomla\Entity\Model;


/**
 * Collection Helper class
 *
 * @since   1.0
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
	/**
	 * The items contained in the collection.
	 *
	 * @var Model[]
	 */
	protected $items = [];

	/**
	 * Create a new collection.
	 *
	 * @param   Model[]  $items array of Models
	 */
	public function __construct($items = [])
	{
		$this->items = $items;
	}

	/**
	 * Get an iterator for the items.
	 *
	 * @return \ArrayIterator
	 */
	#[\ReturnTypeWillChange]
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
	#[\ReturnTypeWillChange]
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
	#[\ReturnTypeWillChange]
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
	#[\ReturnTypeWillChange]
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
	#[\ReturnTypeWillChange]
	public function offsetUnset($offset)
	{
		unset($this->items[$offset]);
	}


	/** Method to convert the Collection to array format.
	 * @return array
	 */
	public function toArray()
	{
		return array_map(
			function ($value)
			{
				if ($value instanceof Model || $value instanceof Collection)
				{
					return $value->toArray();
				}
				else
				{
					// We suppose that the value is a serializable data type
					return $value;
				}

			},
			$this->items
		);
	}
	/**
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return $this->toArray();
	}

	/**
	 * Convert the collection instance to JSON.
	 *
	 * @param   int  $options json_encode Bitmask
	 *
	 * @return string
	 *
	 * @throws JsonEncodingException
	 */
	public function toJson($options = 0)
	{
		$json = json_encode($this->jsonSerialize(), $options);

		if (JSON_ERROR_NONE !== json_last_error())
		{
			throw JsonEncodingException::forModel($this, json_last_error_msg());
		}

		return $json;
	}

	/**
	 * Determine if the collection is empty or not.
	 *
	 * @return boolean
	 */
	public function isEmpty()
	{
		return empty($this->items);
	}

	/**
	 * Get the first item in the collection
	 *
	 * @param   mixed $default default value to be returned when empty Collection
	 * @return mixed
	 */
	public function first($default = false)
	{
		if ($this->isEmpty())
		{
			return $default;
		}

		return $this->items[0];
	}

	/**
	 * Get the all the items in the collection as an array
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->items;
	}

	/**
	 * Find a model in the collection by key.
	 *
	 * @param   mixed  $key     key to be found
	 * @param   mixed  $default default value to be returned when key not found
	 * @return mixed
	 */
	public function find($key, $default = false)
	{
		if ($key instanceof Model)
		{
			$key = $key->getPrimaryKeyValue();
		}

		foreach ($this->items as $item)
		{
			if ($item->getPrimaryKeyValue() == $key)
			{
				return $item;
			}
		}

		return $default;
	}

	/**
	 * Adds an item to the collection
	 *
	 * @param   mixed $item item
	 * @return void
	 */
	public function add($item)
	{
		$this->items[] = $item;
	}

	/**
	 * Count elements of the collection. The return value is cast to an integer.
	 *
	 * @return   integer  The custom count as an integer.
	 *
	 */
	#[\ReturnTypeWillChange]
	public function count()
	{
		return count($this->items);
	}

	/**
	 * Sort through each item with a callback.
	 *
	 * @param   Closure|null  $callback  callback function for sorting
	 * @return static
	 */
	public function sort(Closure $callback = null)
	{
		$items = $this->items;

		$callback
			? usort($items, $callback)
			: sort($items);

		return new static($items);
	}

	/**
	 * Sort through each item by an SQL ordering clause.
	 *
	 * @param   string  $ordering  SQL friendly ordering clause
	 * @return static
	 */
	public function sortByOrdering($ordering)
	{
		$split = explode(' ', $ordering);
		$asc = (count($split) > 1 && strtoupper($split[1] == 'DESC')) ? false : true;

		$items = $this->items;

		usort($items,
			function ($a, $b) use ($split, $asc)
			{
				$valueA = $a->getAttributeNested($split[0]);
				$valueB = $b->getAttributeNested($split[0]);

				if ($valueA == $valueB)
				{
					return 0;
				}

				$return = ($valueA < $valueB) ? -1 : 1;
				$return = ($asc) ? $return : -$return;

				return $return;
			}
		);

		return new static($items);
	}
}
