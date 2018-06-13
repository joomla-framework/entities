<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity;

use ArrayAccess;
use JsonSerializable;
use Joomla\Database\DatabaseDriver;
use Joomla\String\Inflector;
use Joomla\Entity\Exceptions\JsonEncodingException;
use Joomla\Entity\Helpers\StringHelper;
use Joomla\String\Normalise;

/**
 * Base Entity class for items
 *
 * @method static find() find(mixed $id, array $columns = array('*'))
 *
 * @package Joomla\Entity
 * @since 1.0
 */
abstract class Model implements ArrayAccess, JsonSerializable
{
	use ModelHelpers\Attributes;
	use ModelHelpers\Timestamps;
	use ModelHelpers\Relations;

	/**
	 * The connection name for the model.
	 *
	 * @var DatabaseDriver
	 */
	protected $db;

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * The "type" of the auto-incrementing ID.
	 *
	 * @var string
	 */
	protected $primaryKeyType = 'int';

	/**
	 * Indicates if the IDs are auto-incrementing.
	 *
	 * @var boolean
	 */
	public $incrementing = true;

	/**
	 * Indicates if the model exists.
	 *
	 * @var boolean
	 */
	public $exists = false;

	/**
	 * The relations to eager load on every query.
	 *
	 * @var array
	 */
	protected $with = [];

	/**
	 * Create a new Joomla entity model instance.
	 *
	 * @param   DatabaseDriver $db         database driver instance
	 * @param   array          $attributes -> preloads any attributed for the model
	 */
	public function __construct(DatabaseDriver $db = null, array $attributes = array())
	{
		$this->db = $db;

		$this->setAttributes($attributes);

		if (!isset($this->table))
		{
			$this->setDefaultTable();
		}
	}

	/**
	 * @return DatabaseDriver
	 */
	public function getDb()
	{
		return $this->db;
	}

	/**
	 * @param   DatabaseDriver $db database driver
	 * @return void
	 */
	public function setDb($db)
	{
		$this->db = $db;
	}

	/**
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * @return string
	 */
	public function getPrimaryKey()
	{
		return $this->primaryKey;
	}

	/**
	 * @return string
	 */
	public function getQualifiedPrimaryKey()
	{
		return $this->qualifyColumn($this->primaryKey);
	}

	/**
	 * Gets the primary key value
	 * although it should not have mutators or aliases, we use getAttributeValue for consistency.
	 *
	 * @return string
	 */
	public function getPrimaryKeyValue()
	{
		return $this->getAttributeValue($this->primaryKey);
	}

	/**
	 * @param   string $value model's primary key
	 * @return void
	 */
	public function setPrimaryKeyValue($value)
	{
		$this->setAttribute($this->primaryKey, $value);
	}

	/**
	 * @return string
	 */
	public function getPrimaryKeyType(): string
	{
		return $this->primaryKeyType;
	}

	/**
	 * @param   string $primaryKeyType primary key type
	 * @return void
	 */
	public function setPrimaryKeyType(string $primaryKeyType)
	{
		$this->primaryKeyType = $primaryKeyType;
	}

	/**
	 * Qualify the given column name by the model's table.
	 *
	 * @param   string  $column column name to by qualifies
	 * @return string
	 */
	public function qualifyColumn($column)
	{
		if (StringHelper::contains($column, '.'))
		{
			return $column;
		}

		return $this->getTable() . '.' . $column;
	}

	/**
	 * Dynamically retrieve attributes on the model.
	 *
	 * @param   string  $key model's attribute name
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->getAttribute($key);
	}

	/**
	 * Dynamically set attributes on the model.
	 *
	 * @param   string  $key   model's attribute name
	 * @param   mixed   $value model's attribute value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->setAttribute($key, $value);
	}

	/**
	 * @return boolean
	 */
	public function isIncrementing()
	{
		return $this->incrementing;
	}

	/**
	 * Update the model in the database.
	 *
	 * @param   array  $attributes model's attributes
	 *
	 * @return  boolean
	 */
	public function update(array $attributes = [])
	{
		if (!$this->exists)
		{
			 return false;
		}

		return $this->setAttributes($attributes)->save();
	}

	/**
	 * Delete the model from the database.
	 *
	 * @param   mixed  $pk  The primary key to delete (optional - deletes the current model)
	 *
	 * @return  boolean|null
	 */
	public function delete($pk = null)
	{
		if (!is_null($pk))
		{
			$this->setPrimaryKeyValue($pk);
		}
		else
		{
			if (!$this->exists)
			{
				return false;
			}
		}

		/** Here, we'll touch the owning models, ensuring relations consistency.
		 * Only after that we will delete the model instance.
		 */
		$this->touchOwners();

		$query = $this->newQuery();

		return $this->performDelete($query);
	}

	/**
	 * Save the model to the database.
	 *
	 * @return boolean
	 */
	public function save()
	{
		$query = $this->newQuery();

		/** If the model already exists in the database we can just update our record
		 * that is already in this database using the current IDs in this "where"
		 * clause to only update this model. Otherwise, we'll just insert them.
		 */
		if ($this->exists)
		{
			$saved = $this->isDirty() ?
				$this->performUpdate($query) : true;
		}

		/** If the model is brand new, we'll insert it into our database and set the
		 * ID attribute on the model to the value of the newly inserted row's ID
		 * which is typically an auto-increment value managed by the database.
		 */
		else
		{
			$saved = $this->performInsert($query);
		}

		/** If the model is successfully saved, we need to sync the original array
		 * with the new persisted changes, in case there are further actions on the current model.
		 */
		if ($saved)
		{
			$this->syncOriginal();
		}

		return $saved;
	}

	/**
	 * Perform a model insert operation.
	 *
	 * @param   Query  $query instance of query
	 * @return boolean
	 */
	protected function performInsert(Query $query)
	{
		if (empty($this->attributesRaw))
		{
			 return true;
		}

		$success = $query->insert();

		if ($success)
		{
			 $this->exists = true;
		}

		return $success;
	}

	/**
	 * Perform a model insert operation.
	 *
	 * @param   Query  $query istance of query
	 * @return boolean
	 */
	protected function performUpdate($query)
	{
		if (empty($this->attributesRaw))
		{
			 return true;
		}

		$success = $query->update();

		return $success;
	}

	/**
	 * Perform a model insert operation.
	 *
	 * @param   Query  $query istance of query
	 * @return boolean
	 */
	protected function performDelete($query)
	{
		$success = $query->delete();

		if ($success)
		{
			$this->exists = false;
		}

		return $success;
	}


	/**
	 * Get a new query builder for the model's table.
	 *
	 * @return Query
	 */
	public function newQuery()
	{
		$query = new Query($this->db->getQuery(true), $this->db, $this);

		// We are adding the eager loading constrains in every query.
		return $query->with($this->with);
	}

	/**
	 * Handle dynamic method calls into the model.
	 *
	 * @param   string  $method     method called dynamically
	 * @param   array   $parameters parameters to be passed to the dynamic called method
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if (in_array($method, ['increment', 'decrement']))
		{
			return $this->$method(...$parameters);
		}

		return $this->newQuery()->$method(...$parameters);
	}

	/**
	 * Create a new model instance that is existing.
	 *
	 * @param   array $attributesRaw attributes in raw format to be set on the new model instance
	 *
	 * @internal
	 * @return static
	 */
	public function newFromBuilder($attributesRaw = [])
	{
		$model = $this->newInstance($this->db, [], true);

		$model->setAttributesRaw((array) $attributesRaw, true);

		return $model;
	}

	/**
	 * Create a new instance of the given model.
	 *
	 * @param   DatabaseDriver $db         database driver
	 * @param   array          $attributes attributes to be set on the new model instance
	 * @param   bool           $exists     true if the model is already in the database
	 * @return static
	 */
	public function newInstance(DatabaseDriver $db, $attributes = [], $exists = false)
	{
		/** This method just provides a convenient way for us to generate fresh model
		 * instances of this current model. It is particularly useful during the
		 * hydration of new objects via the Query instances.
		 */
		$model = new static($db, (array) $attributes);

		$model->exists = $exists;

		$model->setDb(
			$this->getDb()
		);

		return $model;
	}

	/**
	 * Sets the default value of the table name based on Model class name.
	 * Pluralise the last word in the Model Class name(CamelCase).
	 * Adds underscode between words in table name.
	 *
	 * Examples:
	 * User -> users
	 * UserProfile -> user_profiles
	 *
	 * @return void
	 */
	private function setDefaultTable()
	{
		$className = basename(str_replace('\\', '/', get_class($this)));

		$tableArray = explode(" ", strtolower(Normalise::fromCamelCase($className)));

		$plural = Inflector::pluralize(end($tableArray));

		$tableArray[key($tableArray)] = $plural;

		$table = Normalise::toUnderscoreSeparated(implode(" ", $tableArray));

		$this->table = '#__' . $table;
	}

	/**
	 * Determine if the given attribute exists.
	 *
	 * @param   mixed  $offset key position in array
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return ! is_null($this->getAttribute($offset));
	}

	/**
	 * Get the value for a given offset.
	 *
	 * @param   mixed  $offset key position in array
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->getAttribute($offset);
	}

	/**
	 * Set the value for a given offset.
	 *
	 * @param   mixed  $offset key position in array
	 * @param   mixed  $value  value to be set
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->setAttribute($offset, $value);
	}

	/**
	 * Unset the value for a given offset.
	 *
	 * @param   mixed  $offset key position in array
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->attributesRaw[$offset], $this->relations[$offset]);
	}

	/**
	 * Determine if an attribute or relation exists on the model.
	 *
	 * @param   string  $key attribute name
	 * @return boolean
	 */
	public function __isset($key)
	{
		return $this->offsetExists($key);
	}

	/**
	 * Unset an attribute on the model.
	 *
	 * @param   string  $key attribute name
	 * @return void
	 */
	public function __unset($key)
	{
		$this->offsetUnset($key);
	}

	/**
	 * Convert the model instance to an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		 return array_merge($this->getAttributes(), $this->getRelations());
	}

	/**
	 * Convert the model instance to JSON.
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
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}

	/**
	 * Determine if two models have the same ID, belong to the same table and use the same DatabaseDriver.
	 *
	 * @param   Model|null  $model model to be compared with
	 * @return boolean
	 */
	public function is($model)
	{
		return ! is_null($model) &&
			$this->getPrimaryKeyValue() === $model->getPrimaryKeyValue() &&
			$this->getTable() === $model->getTable() &&
			$this->getDb() === $model->getDb();
	}

	/**
	 * Increment a column's value by a given amount.
	 * $lazy must be set to true when further actions will be taken on to the model before persisting is desired.
	 *
	 * @param   string     $column column to be incremented
	 * @param   float|int  $amount amount to be added to the column value
	 * @param   boolean    $lazy   lazy increment if true
	 * @return integer
	 */
	protected function increment($column, $amount = 1, $lazy = false)
	{
		return $this->incrementOrDecrement($column, $amount, $lazy, 'increment');
	}

	/**
	 * Decrement a column's value by a given amount.
	 *
	 * @param   string     $column column to be decremented
	 * @param   float|int  $amount amount to be subtracted from the column value
	 * @param   boolean    $lazy   lazy increment if true
	 * @return integer
	 */
	protected function decrement($column, $amount = 1, $lazy = false)
	{
		return $this->incrementOrDecrement($column, $amount, $lazy, 'decrement');
	}

	/**
	 * Run the increment or decrement method on the model.
	 *
	 * @param   string     $column column altered in the operation
	 * @param   float|int  $amount amount value
	 * @param   float|int  $lazy   lazy operation if true
	 * @param   string     $method specify increment or decrement operation
	 * @return integer|Model
	 */
	protected function incrementOrDecrement($column, $amount, $lazy, $method)
	{

		$amount = $method == 'increment' ? $amount : $amount * -1;

		$amount = $amount + $this->$column;

		$this->setAttribute($column, $amount);

		if ($lazy)
		{
			return $this;
		}

		if ($this->exists)
		{
			return $this->update();
		}

		return $this->save();
	}

	/**
	 * Method to return the real name of a "special" column such as ordering, hits, published
	 * etc etc. In this way you are free to follow your db naming convention and use the
	 * built in \Joomla functions.
	 *
	 * @param   string  $column  Name of the "special" column (ie ordering, hits)
	 *
	 * @return  string  The string that identify the special
	 */
	public function getColumnAlias($column)
	{
		// Get the column data if set
		if (isset($this->columnAlias[$column]))
		{
			$return = $this->columnAlias[$column];
		}
		else
		{
			$return = $column;
		}

		// Sanitize the name
		$return = preg_replace('#[^A-Z0-9_]#i', '', $return);

		return $return;
	}

	/**
	 * Method to register a column alias for a "special" column.
	 *
	 * @param   string  $column       The "special" column (ie ordering)
	 * @param   string  $columnAlias  The real column name (ie foo_ordering)
	 *
	 * @return  void
	 */
	public function setColumnAlias($column, $columnAlias)
	{
		// Santize the column name alias
		$column = strtolower($column);
		$column = preg_replace('#[^A-Z0-9_]#i', '', $column);

		// Set the column alias internally
		$this->columnAlias[$column] = $columnAlias;
	}

	/**
	 * Begin querying a model with eager loading.
	 *
	 * @param   array|string  $relations relations that should be eager loaded
	 * @return Query|static
	 */
	public function with($relations)
	{
		return $this->newQuery()->with(
			is_string($relations) ? func_get_args() : $relations
		);
	}

	/**
	 * Eager load relations on the model.
	 *
	 * @param   array|string  $relations relations that should be eager loaded
	 * @return $this
	 */
	public function load($relations)
	{
		$query = $this->newQuery()->with(
			is_string($relations) ? func_get_args() : $relations
		);

		$query->eagerLoadRelations(array($this));

		return $this;
	}

	/**
	 * Eager load relations on the model if they are not already eager loaded.
	 *
	 * @param   array|string  $relations relations that should be eager loaded
	 * @return $this
	 */
	public function loadMissing($relations)
	{
		$relations = is_string($relations) ? func_get_args() : $relations;

		return $this->load(
			array_filter($relations,
				function ($relation)
				{
					return ! $this->relationLoaded($relation);
				}
			)
		);
	}
}
