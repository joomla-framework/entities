<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity;

use Joomla\Database\DatabaseFactory;
use Joomla\Database\DatabaseDriver;
use Joomla\String\Inflector;

// Should implement ArrayAccess, JsonSerializable

/**
 * Class Model
 * @package Joomla\Entity
 * @since 1.0
 */
abstract class Model
{
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
	protected $keyType = 'int';

	/**
	 * Indicates if the IDs are auto-incrementing.
	 *
	 * @var boolean
	 */
	public $incrementing = true;

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
	 * Indicates if the model exists.
	 *
	 * @var boolean
	 */
	public $exists = false;

	/**
	 * Create a new Joomla entity model instance.
	 *
	 * @param   array $attributes -> preloads any attributed for the model
	 */
	public function __construct(array $attributes = array())
	{
		$options          = array(
				'host' => '127.0.0.1',
				'user' => 'root',
				'password' => 'root',
				'database' => 'gsoc18',
				'prefix' => 'q371b_',
				'driver' => 'msqli'
			);

		$dbFactory = new DatabaseFactory;
		$this->db = $dbFactory->getDriver($options['driver'], $options);

		$this->setAttributes($this->defaultParams);
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
	 * @param   string $primaryKey model's primary key
	 * @return void
	 */
	public function setPrimaryKey($primaryKey)
	{
		$this->primaryKey = $primaryKey;
	}

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
	 * Get an attribute from the model.
	 *
	 * @param   string  $key model's attribute name
	 * @return mixed
	 */
	public function getAttribute($key)
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
	 * Update the model in the database.
	 *
	 * @param   array  $attributes model's attributes
	 * @return boolean
	 */
	public function update(array $attributes = array())
	{
		if (!$this->exists)
		{
			 return false;
		}

		 // TODO is it a lot better performance wise if we only save the modified attributes?
		 return $this->setAttributes($attributes)->save();
	}

	/**
	 * Delete the model from the database.
	 *
	 * @return boolean|null
	 *
	 * @throws \Exception
	 */
	public function delete()
	{
		if (is_null($this->getPrimaryKey()))
		{
			throw new Exception('No primary key defined on model.');
		}

		if (!$this->exists)
		{
			return false;
		}

		// TODO relations to be taken cared of here.

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

		/** If the model is successfully saved, we need to do a few more things once
		 * that is done. We will call the "saved" method here to run any actions
		 * we need to happen after a model gets successfully saved right here.
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
		if (empty($this->attributes))
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
		if (empty($this->attributes))
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
		$success = $query->delete($this);

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
		return new Query($this->db->getQuery(true), $this->db, $this);
	}

	/**
	 * Handle dynamic method calls into the model.
	 *
	 * @param   string  $method     method called dinamically
	 * @param   array   $parameters parameters to be passed to the dynamic called method
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if (in_array($method, array('increment', 'decrement')))
		{
			return $this->$method(...$parameters);
		}

		return $this->newQuery()->$method(...$parameters);
	}

	/**
	 * Handle dynamic static method calls into the method.
	 *
	 * @param   string  $method     method called dinamically on a static object
	 * @param   array   $parameters parameters to be passed to the dynamic called method
	 * @return mixed
	 */
	public static function __callStatic($method, $parameters)
	{
		return (new static)->$method(...$parameters);
	}

	/**
	 * Create a new model instance that is existing.
	 *
	 * @param   array        $attributes attributes to be set on the new model instance
	 * @param   string|null  $connection database connection to be set on the enw instance
	 * @return static
	 */
	public function newFromBuilder($attributes = array(), $connection = null)
	{
		$model = $this->newInstance(array(), true);

		$model->setRawAttributes((array) $attributes, true);

		return $model;
	}

	/**
	 * Create a new instance of the given model.
	 *
	 * @param   array  $attributes attributes to be set on the new model instance
	 * @param   bool   $exists     true if the model is already in the database
	 * @return static
	 */
	public function newInstance($attributes = array(), $exists = false)
	{
		/** This method just provides a convenient way for us to generate fresh model
		 * instances of this current model. It is particularly useful during the
		 * hydration of new objects via the Query instances.
		 */
		$model = new static((array) $attributes);

		$model->exists = $exists;

		$model->setDb(
			$this->getDb()
		);

		return $model;
	}


	/**
	 * sets the default value of the table name based on Model class name.
	 * @return void
	 */
	private function setDefaultTable()
	{
		$className = strtolower(basename(str_replace('\\', '/', get_class($this))));

		$this->table = '#__' . Inflector::pluralize($className);
	}

}