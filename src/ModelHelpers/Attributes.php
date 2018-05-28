<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\ModelHelpers;

use Joomla\String\Normalise;
use Carbon\Carbon;
use DateTimeInterface;
use Joomla\Entity\Exeptions\JsonEncodingException;
use Joomla\Entity\Helpers\ArrayHelper;

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
	 * The attributes that should be mutated to dates.
	 *
	 * @var array
	 */
	protected $dates = array();

	/**
	 * The storage format of the model's date columns.
	 *
	 * @var string
	 */
	protected $dateFormat;

	/**
	 * Set a given attribute on the model.
	 *
	 * @param   string  $key   model's attribute name
	 * @param   mixed   $value model's attribute value
	 * @return $this
	 */
	public function setAttributeRaw($key, $value)
	{
		$this->attributes[$key] = $value;

		return $this;
	}

	/**
	 * Set a given attribute on the model.
	 *
	 * @param   string  $key   attribute name
	 * @param   mixed   $value value
	 * @return $this
	 */
	public function setAttribute($key, $value)
	{
		/** First we will check for the presence of a mutator for the set operation
		 * which simply lets the developers tweak the attribute as it is set on
		 * the model, such as "json_encoding" an listing of data for storage.
		 */
		if ($this->hasSetMutator($key))
		{
			$method = 'set' . Normalise::toCamelCase($key) . 'Attribute';

			return $this->{$method}($value);
		}

		/** If an attribute is listed as a "date", we'll convert it from a DateTime
		 * instance into a form proper for storage on the database tables using
		 * the connection grammar's date format. We will auto set the values.
		 */
		elseif ($value && $this->isDateAttribute($key))
		{
			$value = $this->fromDateTime($value);
		}

		if ($this->isJsonCastable($key) && ! is_null($value))
		{
			$value = $this->castAttributeAsJson($key, $value);
		}

		/** If this attribute contains a JSON ->, we'll set the proper value in the
		 * attribute's underlying array. This takes care of properly nesting an
		 * attribute in the array's value in the case of deeply nested items.
		 *
		 * JSON usage should be like this: $key='info->name'
		 */
		if (strpos($key, '->') !== false)
		{
			return $this->setJsonAttribute($key, $value);
		}

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

		if (in_array($key, $this->getDates()) && ! is_null($value))
		{
			return $this->asDateTime($value);
		}

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
	public function setAttributesRaw(array $attributes, $sync = false)
	{
		$this->attributes = $attributes;

		if ($sync)
		{
			$this->syncOriginal();
		}

		return $this;
	}

	/**
	 * Get all of the current processed attributes on the model.
	 * processed = dates, cast, mutations
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		/** If an attribute is a date, we will cast it to a string after converting it
		 * to a DateTime / Carbon instance. This is so we will get some consistent
		 * formatting while accessing attributes vs. arraying / JSONing a model.
		 */

		$attributes = $this->getAttributesRaw();

		$attributes = $this->addDateAttributes($attributes);

		$attributes = $this->addMutatedAttributes(
			$attributes, $mutatedAttributes = $this->getMutatorMethods()
		);

		/** Next we will handle any casts that have been setup for this model and cast
		 * the values to their appropriate type. If the attribute has a mutator we
		 * will not perform the cast on those attributes to avoid any confusion.
		 */
		$attributes = $this->addCastAttributes(
			$attributes, $mutatedAttributes
		);

		return $attributes;
	}

	/**
	 * Add the date attributes to the attributes array.
	 *
	 * @param   array  $attributes model attributes - may have been already processed
	 * @return array
	 */
	protected function addDateAttributes(array $attributes)
	{
		foreach ($this->getDates() as $key)
		{
			if (! isset($attributes[$key]))
			{
				continue;
			}

			$attributes[$key] = $this->serializeDate(
				$this->asDateTime($attributes[$key])
			);
		}

		return $attributes;
	}

	/**
	 * Add the mutated attributes to the attributes array.
	 *
	 * @param   array  $attributes        model attributes
	 * @param   array  $mutatedAttributes model mutated attributes
	 * @return array
	 */
	protected function addMutatedAttributes(array $attributes, array $mutatedAttributes)
	{
		foreach ($mutatedAttributes as $key)
		{
			/** We want to spin through all the mutated attributes for this model and call
			 * the mutator for the attribute. We cache off every mutated attributes so
			 * we don't have to constantly check on attributes that actually change.
			 */
			if (! array_key_exists($key, $attributes))
			{
				continue;
			}

			/** Next, we will call the mutator for this attribute so that we can get these
			 * mutated attribute's actual values. After we finish mutating each of the
			 * attributes we will return this final array of the mutated attributes.
			 */
			$attributes[$key] = $this->mutateAttribute($key, $attributes[$key]);
		}

		return $attributes;
	}

	/**
	 * Add the casted attributes to the attributes array.
	 *
	 * @param   array  $attributes        model attributes
	 * @param   array  $mutatedAttributes model mutated attributes
	 * @return array
	 */
	protected function addCastAttributes(array $attributes, array $mutatedAttributes)
	{
		foreach ($this->getCasts() as $key => $value)
		{
			if (! array_key_exists($key, $attributes) || in_array($key, $mutatedAttributes))
			{
				continue;
			}

			/** Here we will cast the attribute. Then, if the cast is a date or datetime cast
			 * then we will serialize the date for the array. This will convert the dates
			 * to strings based on the date format specified for these models.
			 */
			$attributes[$key] = $this->castAttribute(
				$key, $attributes[$key]
			);

			/** If the attribute cast was a date or a datetime, we will serialize the date as
			 * a string. This allows the developers to customize how dates are serialized
			 * into an array without affecting how they are persisted into the storage.
			 */
			if ($attributes[$key] && ($value === 'date' || $value === 'datetime'))
			{
				$attributes[$key] = $this->serializeDate($attributes[$key]);
			}

			if ($attributes[$key] && $this->isCustomDateTimeCast($value))
			{
				$attributes[$key] = $attributes[$key]->format(explode(':', $value, 2)[1]);
			}
		}

		return $attributes;
	}

	/**
	 * Get all of the current attributes on the model in raw format.
	 *
	 * @return array
	 */
	public function getAttributesRaw()
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
	 * Sync only one attribute with the original.
	 *
	 * @param   string $key attribute name
	 * @return $this
	 */
	public function syncOriginalAttribute($key)
	{
		$this->original[$key] = $this->attributes[$key];

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


		foreach ($this->getAttributesRaw() as $key => $value)
		{
			if (!($this->original[$key] == $value))
			{
				$dirty[$key] = $value;
			}
		}

		return $dirty;
	}

	/**
	 * Cast the given attribute to JSON.
	 *
	 * @param   string  $key   attribute name
	 * @param   mixed   $value value
	 * @return string
	 */
	protected function castAttributeAsJson($key, $value)
	{
		$value = $this->asJson($value);

		if ($value === false)
		{
			throw JsonEncodingException::forAttribute(
				$this, $key, json_last_error_msg()
			);
		}

		return $value;
	}

	/**
	 * Encode the given value as JSON.
	 *
	 * @param   mixed  $value array
	 * @return string
	 */
	protected function asJson($value)
	{
		return json_encode($value);
	}

	/**
	 * Decode the given JSON back into an array or object.
	 *
	 * @param   string  $value    value
	 * @param   boolean $asObject When TRUE returned objects will be converted into associative arrays.
	 * @return mixed
	 */
	public function fromJson($value, $asObject = false)
	{
		return json_decode($value, ! $asObject);
	}

	/**
	 * Set a given JSON attribute on the model.
	 *
	 * @param   string  $key   attribute of json type key
	 * @param   mixed   $value json
	 * @return $this
	 */
	public function setJsonAttribute($key, $value)
	{
		list($key, $path) = explode('->', $key, 2);

		$this->attributes[$key] = $this->asJson(
			$this->getNewJsonAttributeArray(
				$path, $key, $value
			)
		);

		return $this;
	}

	/**
	 * Get an array attribute with the given key and value set.
	 *
	 * @param   string  $path  ?
	 * @param   string  $key   ?
	 * @param   mixed   $value ?
	 * @return array
	 */
	protected function getNewJsonAttributeArray($path, $key, $value)
	{

		$array = $this->getJsonAttributeAsArray($key);

		ArrayHelper::set($array, $path, $value);

		return $array;
	}


	/**
	 * Get an array attribute or return an empty array if it is not set.
	 *
	 * @param   string  $key attribute name
	 * @return array
	 */
	protected function getJsonAttributeAsArray($key)
	{
		return isset($this->attributes[$key]) ?
			$this->fromJson($this->attributes[$key]) : array();
	}

	/**
	 * Return a timestamp as DateTime object with time set to 00:00:00.
	 *
	 * @param   mixed  $value value
	 * @return \Carbon\Carbon
	 */
	protected function asDate($value)
	{
		return $this->asDateTime($value)->startOfDay();
	}

	/**
	 * Return a timestamp as DateTime object.
	 *
	 * @param   mixed  $value value
	 * @return \Carbon\Carbon
	 */
	protected function asDateTime($value)
	{
		/** If this value is already a Carbon instance, we shall just return it as is.
		 * This prevents us having to re-instantiate a Carbon instance when we know
		 * it already is one, which wouldn't be fulfilled by the DateTime check.
		 */
		if ($value instanceof Carbon)
		{
			return $value;
		}

		/** If the value is already a DateTime instance, we will just skip the rest of
		 * these checks since they will be a waste of time, and hinder performance
		 * when checking the field. We will just return the DateTime right away.
		 */
		if ($value instanceof DateTimeInterface)
		{
			return new Carbon(
				$value->format('Y-m-d H:i:s.u'), $value->getTimezone()
			);
		}

		/** If this value is an integer, we will assume it is a UNIX timestamp's value
		 * and format a Carbon object from this timestamp. This allows flexibility
		 * when defining your date fields as they might be UNIX timestamps here.
		 */
		if (is_numeric($value))
		{
			return Carbon::createFromTimestamp($value);
		}

		/** If the value is in simply year, month, day format, we will instantiate the
		 * Carbon instances from that format. Again, this provides for simple date
		 * fields on the database, while still supporting Carbonized conversion.
		 */
		if ($this->isStandardDateFormat($value))
		{
			return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
		}

		/** Finally, we will just assume this date is in the format used by default on
		 * the database connection and use that format to create the Carbon object
		 * that is returned back out to the developers after we convert it here.
		 */
		return Carbon::createFromFormat(
			str_replace('.v', '.u', $this->getDateFormat()), $value
		);
	}

	/**
	 * Determine if the given value is a standard date format.
	 *
	 * @param   string  $value value
	 * @return boolean
	 */
	protected function isStandardDateFormat($value)
	{
		return preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
	}

	/**
	 * Convert a DateTime to a storable string.
	 *
	 * @param   \DateTime|int  $value value
	 * @return string
	 */
	public function fromDateTime($value)
	{
		return empty($value) ? $value : $this->asDateTime($value)->format(
			$this->getDateFormat()
		);
	}

	/**
	 * Return a timestamp as unix timestamp.
	 *
	 * @param   mixed  $value value
	 * @return int
	 */
	protected function asTimestamp($value)
	{
		return $this->asDateTime($value)->getTimestamp();
	}

	/**
	 * Prepare a date for array / JSON serialization.
	 *
	 * @param   \DateTimeInterface  $date date
	 * @return string
	 */
	protected function serializeDate(DateTimeInterface $date)
	{
		return $date->format($this->getDateFormat());
	}

	/**
	 * Get the attributes that should be converted to dates.
	 *
	 * @return array
	 */
	public function getDates()
	{
		$defaults = array(static::CREATED_AT, static::UPDATED_AT);

		return $this->usesTimestamps()
			? array_unique(array_merge($this->dates, $defaults))
			: $this->dates;
	}

	/**
	 * Get the format for database stored dates.
	 *
	 * @return string
	 */
	public function getDateFormat()
	{
		return $this->dateFormat ?: $this->getConnection()->getQueryGrammar()->getDateFormat();
	}

	/**
	 * Set the date format used by the model.
	 *
	 * @param   string  $format date format
	 * @return $this
	 */
	public function setDateFormat($format)
	{
		$this->dateFormat = $format;

		return $this;
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
	 * Determine if the given attribute is a date or date castable.
	 *
	 * @param   string  $key attribute name
	 * @return boolean
	 */
	protected function isDateAttribute($key)
	{
		return in_array($key, $this->getDates()) || $this->isDateCastable($key);
	}

	/**
	 * Determine whether a value is Date / DateTime castable for inbound manipulation.
	 *
	 * @param   string  $key attribute name
	 * @return boolean
	 */
	protected function isDateCastable($key)
	{
		return $this->hasCast($key, array('date', 'datetime'));
	}

	/**
	 * Determine whether a value is JSON castable for inbound manipulation.
	 *
	 * @param   string  $key attribute name
	 * @return boolean
	 */
	protected function isJsonCastable($key)
	{
		return $this->hasCast($key, array('array', 'json', 'object', 'collection'));
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
	 * Determine if a set mutator exists for an attribute.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function hasSetMutator($key)
	{
		return method_exists($this, 'set' . Normalise::toCamelCase($key) . 'Attribute');
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
	 * TODO do we need to cache this?, if so, what cache do we use?
	 * Get the mutated attributes for a given instance.
	 *
	 * @return array

	public function getMutatedAttributes()
	{
		$class = static::class;

		if (! isset(static::$mutatorCache[$class]))
		{
			static::cacheMutatedAttributes($class);
		}

		return static::$mutatorCache[$class];
	}


	 * Extract and cache all the mutated attributes of a class.
	 *
	 * @param   string  $class ?
	 * @return void

	public static function cacheMutatedAttributes($class)
	{
		$mutatedAttributes = static::getMutatorMethods($class);

		$cache = array();

		foreach ($mutatedAttributes as $mutatedAttribute)
		{
			$cache[] = lcfirst(Normalise::toCamelCase($mutatedAttribute));
		}

		static::$mutatorCache[$class] = $cache;
	}*/

	/**
	 * Get all of the attribute mutator methods.
	 *
	 * @return array
	 */
	protected static function getMutatorMethods()
	{
		$class = static::class;

		preg_match_all('/(?<=^|;)get([^;]+?)Attribute(;|$)/', implode(';', get_class_methods($class)), $matches);

		$result = array();

		foreach ($matches[1] as $match)
		{
			$result[] = lcfirst(Normalise::toCamelCase($match));
		}

		return $result;
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
				return $this->asDate($value);
			case 'datetime':
			case 'custom_datetime':
				return $this->asDateTime($value);
			case 'timestamp':
				return $this->asTimestamp($value);
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
