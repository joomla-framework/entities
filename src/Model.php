<?php

use Joomla\Database\DatabaseFactory;
use Joomla\Database\DatabaseDriver;

abstract class Model implements ArrayAccess, JsonSerializable {
	/**
	 * The connection name for the model.
	 *
	 * @var DatabaseDriver
	 */
	protected $_db;

	/**
	 * Database factory.
	 *
	 * @var DatabaseFactory
	 */
	protected $_dbFactory;

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
	 * @var bool
	 */
	public $incrementing = true;

	/**
	 * The model's attributes.
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * The model's original attributes.
	 *
	 * @var array
	 */
	protected $original = [];

	/**
	 * Indicates if the model exists.
	 *
	 * @var bool
	 */
	public $exists = false;

	/**
	 * Create a new Joomla entity model instance.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	public function __construct(array $attributes = [])
	{
                $driver = 'mysqli';
		$options = array(
				'host' => '127.0.0.1',
				'user' => 'root',
				'password' => 'root',
				'prefix' => 'root',
				'database' => 'gsoc18',
			);

                $this->_db = $this->_dbFactory->getDriver($driver, $options);

		$this->setAttributes($attributes);
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
	 * @param string $primaryKey
	 */
	public function setPrimaryKey($primaryKey)
	{
		$this->primaryKey = $primaryKey;
	}

	/**
	 * Set a given attribute on the model.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
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
	 * @param  string  $key
	 * @return mixed
	 */
	public function getAttribute($key){
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
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->getAttribute($key);
	}

	/**
	 * Dynamically set attributes on the model.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->setAttribute($key, $value);
	}

	/**
	 * @return bool
	 */
	public function isIncrementing()
	{
		return $this->incrementing;
	}



	/**
	 * Fill the model with an array of attributes.
	 *
	 * @param  array  $attributes
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
	 * @param  array|string|null  $attributes
	 * @return bool
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
		$dirty = [];

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
	 * @param  array  $changes
	 * @param  array|string|null  $attributes
	 * @return bool
	 */
	protected function hasChanges($changes, $attributes = [])
	{
		// If no specific attributes were provided, we will just see if the dirty array
		// already contains any attributes. If it does we will just return that this
		// count is greater than zero. Else, we need to check specific attributes.
		if (empty($attributes)) {
			return count($changes) > 0;
		}

		// Here we will spin through every attribute and see if this is in the array of
		// dirty attributes. If it is, we will return true and if we make it through
		// all of the attributes for the entire array we will return false at end.
		foreach ($attributes as $attribute) {
			if (array_key_exists($attribute, $changes)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Update the model in the database.
	 *
	 * @param  array  $attributes
	 * @param  array  $options
	 * @return bool
	 */
	 public function update(array $attributes = [], array $options = [])
	 {
		 if (!$this->exists) {
			 return false;
		 }

		 // TODO is it a lot better performance wise if we only save the modified attributes?
		 return $this->setAttributes($attributes)->save($options);
	 }

	/**
	 * Delete the model from the database.
	 *
	 * @return bool|null
	 *
	 * @throws \Exception
	 */
	public function delete()
	{
		if (is_null($this->getPrimaryKey())) {
			throw new Exception('No primary key defined on model.');
		}

		if (!$this->exists) {
			return false;
		}

		//TODO relations to be taken cared of here.

		$query = $this->newQuery();

		return $this->performDelete($query);
	}

	/**
	 * Save the model to the database.
	 *
	 * @param  array  $options
	 * @return bool
	 */
	public function save(array $options = [])
	{
		$query = $this->newQuery();

		// If the model already exists in the database we can just update our record
		// that is already in this database using the current IDs in this "where"
		// clause to only update this model. Otherwise, we'll just insert them.
		if ($this->exists) {
			$saved = $this->isDirty() ?
				$this->performUpdate($query) : true;
		}

		// If the model is brand new, we'll insert it into our database and set the
		// ID attribute on the model to the value of the newly inserted row's ID
		// which is typically an auto-increment value managed by the database.
		else {
			$saved = $this->performInsert($query);
		}

		// If the model is successfully saved, we need to do a few more things once
		// that is done. We will call the "saved" method here to run any actions
		// we need to happen after a model gets successfully saved right here.
		if ($saved) {
			$this->syncOriginal();
		}

		return $saved;
	}

	/**
	 * Perform a model insert operation.
	 *
	 * @param  Query  $query
	 * @return bool
	 */
	 protected function performInsert(Query $query)
	 {
		 if (empty($this->attributes)) {
			 return true;
		 }

		 $success = $query->insert($this);

		 if ($success){
			 $this->exists = true;
		 }

		 return $success;
	 }

	/**
	 * Perform a model insert operation.
	 *
	 * @param  Query  $query
	 * @return bool
	 */
	 protected function performUpdate($query)
	 {
		 if (empty($this->attributes)) {
			 return true;
		 }

		 $success = $query->update($this);

		 return $success;
	 }

	/**
	 * Perform a model insert operation.
	 *
	 * @param  Query  $query
	 * @return bool
	 */
	protected function performDelete($query)
	{
		$success = $query->delete($this);

		return $success;
	}


	/**
	 * Get a new query builder for the model's table.
	 *
	 * @return Query
	 */
	public function newQuery()
	{
		return new Query($this->_dbFactory->getQuery($this->_db->getName(), $this->_db), $this->_db);
	}
}
