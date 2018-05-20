<?php
/**
 * Created by PhpStorm.
 * User: isac
 * Date: 20/05/2018
 * Time: 2:30 PM
 */

use \Joomla\Database\QueryInterface;
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
	protected $_query;

	/**
	 * The connection name for the model.
	 *
	 * @var DatabaseDriver
	 */
	protected $_db;

	/**
	 * Create a new Query instance.
	 *
	 * @param  QueryInterface  $query
	 * @param  DatabaseDriver $db
	 * @return void
	 */
	public function __construct($query, $db)
	{
		$this->_query = $query;
		$this->_db = $db;
	}


	/**
	 * Inserts a single instance of a model.
	 *
	 * @param Model $model
	 *
	 * @return bool
	 */
	public function insert(&$model)
	{
		$fields       = [];
		$values       = [];

		// Iterate over the object variables to build the query fields and values.
		foreach ($model->getAttributes() as $k => $v)
		{
			// Prepare and sanitize the fields and values for the database query.
			$fields[] = $this->_db->quoteName($k);
			$values[] = $this->_db->quote($v);
		}

		// Create the base insert statement.
		$this->_query->insert($this->_db->quoteName($model->getTable()))
			->columns($fields)
			->values(implode(',', $values));

		// Set the query and execute the insert.
		$success = $this->_db->setQuery($this->_query)->execute();

		if ($model->isIncrementing())
		{
			$id = $this->_db->insertid();
			$key = $model->getPrimaryKey();
			// Update the primary key if it exists.
			if ($key && $id && is_string($key))
			{
				$model->setPrimaryKey($id);
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
	public function update(&$model)
	{
		$fields       = [];

		$statement = 'UPDATE ' . $this->_db->quoteName($model->getTable()) . ' SET %s WHERE %s';

		$where = $this->getWherePrimaryKey($model);

		// Iterate over the object variables to build the query fields and values.
		foreach ($model->getDirty() as $k => $v)
		{
			if ($v == null){
				$v = 'NULL';
			}
			// Prepare and sanitize the fields and values for the database query.
			$fields[] = $this->_db->quoteName($k) . '=' . $this->_db->quote($v);
		}

		if (empty($fields))
		{
			return true;
		}

		// Set the query and execute the insert.
		$success = $this->_db->setQuery(sprintf($statement, implode(',', $fields), $where))->execute();

		return $success;
	}

	/**
	 * Deletes a single instance of a model.
	 *
	 * @param Model $model
	 *
	 * @return bool
	 */
	public function delete(&$model)
	{
		$this->_query->delete($model->getTable())->where($this->getWherePrimaryKey($model));

		// Set the query and execute the insert.
		$success = $this->_db->setQuery($this->_query)->execute();

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
		return $this->_db->quoteName($key) . '=' . $this->_db->quote($model->$key);

	}

}