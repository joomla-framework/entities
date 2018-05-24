<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Helpers;
use Joomla\String\Normalise;


/**
 * Trait Attributes
 * @package Joomla\Entity\Helpers
 * @since 1.0
 */
trait Attributes
{
	/**
	 * The model's attributes.
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * The model's default params.
	 *
	 * TODO this is a hack because an error is thrown if some params are not set for inserts.
	 * @var array
	 */
	protected $defaultParams = array();

	/**
	 * The model's original attributes.
	 *
	 * @var array
	 */
	protected $original = array();

	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array
	 */
	protected $casts = array();

	/**
	 * Set a given attribute on the model.
	 *
	 * @param   string  $key   model's attribute name
	 * @param   mixed   $value model's attribute value
	 * @return $this
	 */
	public function setAttribute($key, $value)
	{
		$this->attributes[$key] = $value;

		return $this;
	}

	/**
	 * Get an raw attribute from the model.
	 *
	 * @param   string  $key model's attribute name
	 * @return mixed
	 */
	public function getAttributeRaw($key)
	{
		if (!$key)
		{
			return null;
		}

		if (array_key_exists($key, $this->attributes))
		{
			return $this->attributes[$key];
		}

		return null;
	}

	/**
	 * Get an attribute from the model. (including mutations)
	 *
	 * @param   string  $key attribute name
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		if (!$key)
		{
			return null;
		}

		/** If the attribute exists in the attribute array or has a "get" mutator we will
		 * get the attribute's value. Otherwise, we will proceed as if the developers
		 * are asking for a relationship's value. This covers both types of values.
		 */
		if (array_key_exists($key, $this->attributes) || $this->hasGetMutator($key))
		{
			return $this->getAttributeValue($key);
		}

		/** Here we will determine if the model base class itself contains this given key
		 * since we don't want to treat any of those methods as relationships because
		 * they are all intended as helper methods and none of these are relations.
		 */
		if (method_exists(self::class, $key))
		{
			return null;
		}

		// TODO relations
		// return $this->getRelationValue($key);

		return null;
	}

	/**
	 * Get a plain attribute (not a relationship).
	 *
	 * @param   string  $key attribute name
	 * @return mixed
	 */
	public function getAttributeValue($key)
	{
		$value = $this->getAttributeRaw($key);

		/** If the attribute has a get mutator, we will call that then return what
		 * it returns as the value, which is useful for transforming values on
		 * retrieval from the model to a form that is more useful for usage.
		 */
		if ($this->hasGetMutator($key))
		{
			return $this->mutateAttribute($key, $value);
		}

		/** If the attribute exists within the cast array, we will convert it to
		 * an appropriate native PHP type dependant upon the associated value
		 * given with the key in the pair. Dayle made this comment line up.
		 */
		if ($this->hasCast($key))
		{
			return $this->castAttribute($key, $value);
		}

		/** If the attribute is listed as a date, we will convert it to a DateTime
		 * instance on retrieval, which makes it quite convenient to work with
		 * date fields without having to create a mutator for each property.
		 */
		/*
		 * TODO handle dates
		if (in_array($key, $this->getDates()) && ! is_null($value))
		{
			return $this->asDateTime($value);
		}
		*/

		return $value;
	}

	/**
	 * Fill the model with an array of attributes.
	 *
	 * @param   array  $attributes model's attributes
	 * @return $this
	 *
	 */
	public function setAttributes(array $attributes)
	{
		foreach ($attributes as $key => $value)
		{
			$this->setAttribute($key, $value);
		}

		return $this;
	}

	/**
	 * Set the array of model attributes. No checking is done.
	 *
	 * @param   array    $attributes model's attributes
	 * @param   boolean  $sync       true if the data has been persisted
	 * @return $this
	 */
	public function setRawAttributes(array $attributes, $sync = false)
	{
		$this->attributes = $attributes;

		if ($sync)
		{
			$this->syncOriginal();
		}

		return $this;
	}

	/**
	 * Get all of the current attributes on the model.
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Sync the original attributes with the current.
	 *
	 * @return $this
	 */
	public function syncOriginal()
	{
		$this->original = $this->attributes;

		return $this;
	}

	/**
	 * Determine if the model or given attribute(s) have been modified.
	 *
	 * @param   array|string|null  $attributes model's attributes
	 * @return boolean
	 */
	public function isDirty($attributes = null)
	{
		return $this->hasChanges(
			$this->getDirty(), is_array($attributes) ? $attributes : func_get_args()
		);
	}

	/**
	 * Get the attributes that have been changed since last sync.
	 *
	 * @return array
	 */
	public function getDirty()
	{
		$dirty = array();

		foreach ($this->getAttributes() as $key => $value)
		{
			if (!$this->original[$key] == $value)
			{
				$dirty[$key] = $value;
			}
		}

		return $dirty;
	}

	/**
	 * Determine if the given attributes were changed.
	 *
	 * @param   array              $changes    changes in attributes
	 * @param   array|string|null  $attributes attributes, optional
	 * @return boolean
	 */
	protected function hasChanges($changes, $attributes = array())
	{
		/** If no specific attributes were provided, we will just see if the dirty array
		 * already contains any attributes. If it does we will just return that this
		 * count is greater than zero. Else, we need to check specific attributes.
		 */
		if (empty($attributes))
		{
			return count($changes) > 0;
		}

		/** Here we will spin through every attribute and see if this is in the array of
		 * dirty attributes. If it is, we will return true and if we make it through
		 * all of the attributes for the entire array we will return false at end.
		 */
		foreach ($attributes as $attribute)
		{
			if (array_key_exists($attribute, $changes))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if a get mutator exists for an attribute.
	 *
	 * @param   string  $key attribute name
	 * @return boolean
	 */
	public function hasGetMutator($key)
	{
		return method_exists($this, 'get' . Normalise::toCamelCase($key) . 'Attribute');
	}

	/**
	 * Get the value of an attribute using its mutator.
	 *
	 * @param   string $key   attribute name
	 * @param   mixed  $value value to be mutated
	 * @return mixed
	 */
	protected function mutateAttribute($key, $value)
	{
		return $this->{'get' . Normalise::toCamelCase($key) . 'Attribute'}($value);
	}

	/**
	 * Determine whether an attribute should be cast to a native type.
	 *
	 * @param   string            $key   attribute name
	 * @param   array|string|null $types ?
	 * @return boolean
	 */
	public function hasCast($key, $types = null)
	{
		if (array_key_exists($key, $this->getCasts()))
		{
			return $types ? in_array($this->getCastType($key), (array) $types, true) : true;
		}

		return false;
	}

	/**
	 * Get the casts array.
	 *
	 * @return array
	 */
	public function getCasts()
	{
		if ($this->isIncrementing())
		{
			return array_merge(array($this->getPrimaryKey() => $this->getPrimaryKeyType()), $this->casts);
		}

		return $this->casts;
	}

	/**
	 * Cast an attribute to a native PHP type.
	 *
	 * @param   string $key   ?
	 * @param   mixed  $value ?
	 * @return mixed
	 */
	protected function castAttribute($key, $value)
	{
		if (is_null($value))
		{
			return $value;
		}

		switch ($this->getCastType($key))
		{
			case 'int':
			case 'integer':
				return (int) $value;
			case 'real':
			case 'float':
			case 'double':
				return (float) $value;
			case 'string':
				return (string) $value;
			case 'bool':
			case 'boolean':
				return (bool) $value;
			case 'object':
				return $this->fromJson($value, true);
			case 'array':
			case 'json':
				return $this->fromJson($value);
			case 'date':
				// TODO
			case 'datetime':
			case 'custom_datetime': // TODO
			case 'timestamp': // TODO
			default:
				return $value;
		}
	}

	/**
	 * Get the type of cast for a model attribute.
	 *
	 * @param   string $key ?
	 * @return string
	 */
	protected function getCastType($key)
	{
		if ($this->isCustomDateTimeCast($this->getCasts()[$key]))
		{
			return 'custom_datetime';
		}

		return trim(strtolower($this->getCasts()[$key]));
	}

	/**
	 * Decode the given JSON back into an array or object.
	 *
	 * @param   string  $value    ?
	 * @param   bool    $asObject ?
	 * @return mixed
	 */
	public function fromJson($value, $asObject = false)
	{
		return json_decode($value, ! $asObject);
	}

	/**
	 * Determine if the cast type is a custom date time cast.
	 *
	 * @param   string  $cast ?
	 * @return boolean
	 */
	protected function isCustomDateTimeCast($cast)
	{
		return strncmp($cast, 'date:', 5) === 0 ||
			strncmp($cast, 'datetime:', 9) === 0;
	}

}
