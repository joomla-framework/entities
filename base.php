<?php

abstract class Model implements ArrayAccess, JsonSerializable {
	/**
	 * The connection name for the model.
	 *
	 * @var string
	 */
	protected $connection;

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
	 * Set a given attribute on the model.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return $this
	 */
	abstract public function setAttribute($key, $value);

	/**
	 * Get an attribute from the model.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	abstract public function getAttribute($key);

	/**
	 * Update the model in the database.
	 *
	 * @param  array  $attributes
	 * @param  array  $options
	 * @return bool
	 */
	abstract public function update(array $attributes = [], array $options = []);

	/**
	 * Delete the model from the database.
	 *
	 * @return bool|null
	 *
	 * @throws \Exception
	 */
	abstract public function delete();

	/**
	 * Perform a model insert operation.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $query
	 * @return bool
	 */
	abstract protected function performInsert(Builder $query);

	/**
	 * Get a new query builder for the model's table.
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	abstract public function newQuery();
}
