<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity;

use Joomla\Database\QueryInterface;
use Joomla\Database\DatabaseDriver;

/**
 * Class Query
 */
class Query
{
	/**
	 * The Joomla QueryInterface.
	 *
	 * @var QueryInterface
	 */
	protected $query;

	/**
	 * The connection name for the model.
	 *
	 * @var DatabaseDriver
	 */
	protected $db;

	/**
	 * The connection name for the model.
	 *
	 * @var Model
	 */
	protected $model;

	/**
	 * The methods that should be returned from Joomla Database query builder.
	 *
	 * @var array
	 */
	protected $passthru = [
		'select', 'where', 'from'
	];

	/**
	 * Create a new Query instance.
	 *
	 * @param  QueryInterface  $query
	 * @param  DatabaseDriver $db
	 * @param  Model $model
	 * @return void
	 */
	public function __construct($query, $db, &$model)
	{
		$this->query = $query;
		$this->db    = $db;
		$this->model = $model;

	}


	/**
	 * Inserts a single instance of a model.
	 *
	 * @param Model $model
	 *
	 * @return bool
	 */
	public function insert()
	{
		$fields       = [];
		$values       = [];

		// Iterate over the object variables to build the query fields and values.
		foreach ($this->model->getAttributes() as $k => $v)
		{
			// Prepare and sanitize the fields and values for the database query.
			$fields[] = $this->db->quoteName($k);
			$values[] = $this->db->quote($v);
		}

		// Create the base insert statement.
		$this->query->insert($this->db->quoteName($this->model->getTable()))
			->columns($fields)
			->values(implode(',', $values));

		// Set the query and execute the insert.
		$success = $this->db->setQuery($this->query)->execute();

		if ($this->model->isIncrementing())
		{
			$id = $this->db->insertid();
			$key = $this->model->getPrimaryKey();
			// Update the primary key if it exists.
			if ($key && $id && is_string($key))
			{
				$this->model->setPrimaryKey($id);
			}
		}

		return $success;
	}

	/**
	 * Updates a single instance of a model.
	 *
	 * @param Model $model
	 *
	 * @return bool
	 */
	public function update()
	{
		$fields       = [];

		$statement = 'UPDATE ' . $this->db->quoteName($this->model->getTable()) . ' SET %s WHERE %s';

		$where = $this->getWherePrimaryKey($this->model);

		// Iterate over the object variables to build the query fields and values.
		foreach ($this->model->getDirty() as $k => $v)
		{
			if ($v == null){
				$v = 'NULL';
			}
			// Prepare and sanitize the fields and values for the database query.
			$fields[] = $this->db->quoteName($k) . '=' . $this->db->quote($v);
		}

		if (empty($fields))
		{
			return true;
		}

		// Set the query and execute the insert.
		$success = $this->db->setQuery(sprintf($statement, implode(',', $fields), $where))->execute();

		return $success;
	}

	/**
	 * Deletes a single instance of a model.
	 *
	 * @param Model $model
	 *
	 * @return bool
	 */
	public function delete()
	{
		$this->query->delete($this->model->getTable())->where($this->getWherePrimaryKey($this->model));

		// Set the query and execute the insert.
		$success = $this->db->setQuery($this->query)->execute();

		return $success;
	}


	/**
	 * Constructs the where clause based on the primary key
	 *
	 * @param Model $model
	 *
	 * @return string
	 */
	protected function getWherePrimaryKey($model)
	{
		$key = $model->getPrimaryKey();
		return $this->db->quoteName($key) . '=' . $this->db->quote($model->$key);

	}

	/**
	 * Find a model by its primary key.
	 *
	 * @param  mixed  $id
	 * @param  array  $columns
	 * @return Model
	 */
	public function find($id, $columns = ['*'])
	{
		$this->from($this->model->getTable())->select($columns)->whereKey($id);
		$item = $this->db->setQuery($this->query)->loadAssoc();

		//TODO something like first() from Collection would make this nicer
		//TODO, what fi the key does not exits, error handling
		return $this->hydrate([$item])[0];
	}

	/**
	 * Add a where clause on the primary key to the query.
	 *
	 * @param  mixed  $id
	 * @return $this
	 */
	public function whereKey($id)
	{
		return $this->where($this->model->getPrimaryKey() .' = ' . $id);
	}

	/**
	 * Create a collection of models from plain arrays.
	 *
	 * @param  array  $items
	 * @return array
	 */
	// TODO shall we use something like Collection?
	public function hydrate(array $items)
	{
		$instance = $this->newModelInstance();

		return array_map(function ($item) use ($instance) {
			return $instance->newFromBuilder($item);
		}, $items);
	}

	/**
	 * Create a new instance of the model being queried.
	 *
	 * @param  array  $attributes
	 * @return Model
	 */
	public function newModelInstance($attributes = [])
	{
		$model = $this->model->newInstance($attributes);
		$model->setDriver($this->db->getServerType());

		return $model;
	}

	/**
	 * Dynamically handle calls into the query instance.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if (in_array($method, $this->passthru))
		{
			$this->query->{$method}(...$parameters);
		}
		else
		{
			throw new BadMethodCallException(sprintf(
				'Method %s does not exist in QueryInterface.',  $method
			));
		}

		return $this;
	}

}