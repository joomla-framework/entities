<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity;

use BadMethodCallException;
use Joomla\Entity\Exceptions\RelationNotFoundException;
use Joomla\Database\Query\LimitableInterface;
use Joomla\Database\QueryInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Entity\Helpers\Collection;
use Joomla\Entity\Helpers\StringHelper;
use Joomla\Entity\Relations\Relation;
use Closure;

/**
 * Class Query
 * @since 1.0
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
	 * The relations that should be eager loaded.
	 *
	 * @var array
	 */
	protected $eagerLoad = array();

	/**
	 * The methods that should be returned from query builder.
	 *
	 * @var array
	 */
	protected $passThrough = array(
		'select', 'where', 'from', 'having', 'join', 'order', 'setLimit'
	);

	/**
	 * Create a new Query instance.
	 *
	 * @param   QueryInterface $query Joomla Database QueryInterface instantiated in the model
	 * @param   DatabaseDriver $db    Joomla DatabaseDriver
	 * @param   Model          $model Model passed by reference
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
	 *
	 * @return boolean
	 */
	public function insert()
	{
		$fields = array();
		$values = array();

		// Iterate over the object variables to build the query fields and values.
		foreach ($this->model->getAttributesRaw() as $k => $v)
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
				$this->model->setPrimaryKeyValue($id);
			}
		}

		return $success;
	}

	/**
	 * Updates a single instance of a model.
	 *
	 * @return boolean
	 */
	public function update()
	{
		$fields = array();

		// Iterate over the object variables to build the query fields and values.
		foreach ($this->model->getDirty() as $k => $v)
		{
			if ($v == null)
			{
				$v = 'NULL';
			}

			// Prepare and sanitize the fields and values for the database query.
			$fields[] = $this->db->quoteName($k) . '=' . $this->db->quote($v);
		}

		if (empty($fields))
		{
			return true;
		}

		$this->query = $this->db->getQuery(true);
		$this->query->update($this->model->getTable())
			->set($fields)
			->where($this->getWherePrimaryKey());

		// Set the query and execute the insert.
		$success = $this->db->setQuery($this->query)->execute();

		return $success;
	}

	/**
	 * Deletes a single instance of a model.
	 *
	 * @return boolean
	 */
	public function delete()
	{
		$this->query->delete($this->model->getTable())->where($this->getWherePrimaryKey());

		// Set the query and execute the insert.
		$success = $this->db->setQuery($this->query)->execute();

		return $success;
	}

	/**
	 * Constructs the where clause based on the primary key
	 *
	 * @return string
	 */
	protected function getWherePrimaryKey()
	{
		$key = $this->model->getPrimaryKey();

		return $this->db->quoteName($key) . '=' . $this->db->quote($this->model->$key);
	}

	/**
	 * Find a model by its primary key.
	 *
	 * @param   mixed  $id      primary key
	 * @param   array  $columns columns to be selected in query
	 * @return Model|boolean
	 */
	public function find($id, $columns = array('*'))
	{
		$this->whereKey($id);

		$models = $this->get($columns);

		if ($models->isEmpty())
		{
			return false;
		}

		return $models->first();
	}

	/**
	 * Find last inserted.
	 *
	 * @param   array  $columns columns to be selected in query
	 * @return Model|boolean
	 * @throws \BadMethodCallException
	 */
	public function findLast($columns = array('*'))
	{
		if (!($this->query instanceof LimitableInterface))
		{
			throw new \BadMethodCallException('Query class does not support limit by');
		}

		$this->query->order('id DESC')
			->setLimit(1);

		$models = $this->get();

		if ($models->isEmpty())
		{
			return false;
		}

		return $models->first();
	}

	/**
	 * Add a where clause on the primary key to the query.
	 *
	 * @param   mixed $id primary key
	 * @return $this
	 */
	public function whereKey($id)
	{
		$this->query->where($this->model->getPrimaryKey() . ' = ' . $id);

		return $this;
	}

	/**
	 * Add a where clause on a columnt to not be null.
	 *
	 * @param   mixed $column column
	 * @return $this
	 */
	public function whereNotNull($column)
	{
		$this->query->where($column . ' NOT NULL');

		return $this;
	}

	/**
	 * Create a collection of models from plain arrays.
	 *
	 * @param   array $items array of results from the database query
	 * @return Collection
	 */
	public function hydrate(array $items)
	{
		$instance = $this->model->newInstance($this->db);

		$models = array_map(
			function ($item) use ($instance) {
				return $instance->newFromBuilder($item);
			}, $items
		);

		return new Collection($models);
	}

	/**
	 * Dynamically handle calls into the query instance.
	 *
	 * @param   string  $method     method called dinamically
	 * @param   array   $parameters parameters to be passed to the dynamic called method
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if (!in_array($method, $this->passThrough))
		{
			throw new BadMethodCallException(sprintf('Method %s does not exist or is not exposed from QueryInterface.',  $method));
		}

		$this->query->{$method}(...$parameters);

		return $this;
	}

	/**
	 * @return Model
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * Finds all the Models with eager relations loaded
	 *
	 * @param   array  $columns columns to be selected in query
	 * @return Collection
	 */
	public function get($columns = array('*'))
	{
		/** If we actually found models we will also eager load any relations that
		 * have been specified as needing to be eager loaded
		 */
		$models = $this->getModels($columns);

		if (count($models) > 0)
		{
			$models = $this->eagerLoadRelations($models);
		}

		return new Collection($models);
	}

	/**
	 * Get the hydrated models without eager loading.
	 *
	 * @param   array  $columns columns to be selected in query
	 * @return Model[]
	 */
	public function getModels($columns = array('*'))
	{
		$this->query->select($columns)
			->from($this->model->getTable());

		$items = $this->db->setQuery($this->query)->loadAssocList();

		return $this->hydrate($items)->all();
	}

	/**
	 * Set the relations that should be eager loaded.
	 *
	 * @param   mixed  $relations relations that should be eager loaded
	 * @return $this
	 */
	public function with($relations)
	{
		$eagerLoad = $this->parseWithRelations($relations);

		$this->eagerLoad = array_merge($this->eagerLoad, $eagerLoad);

		return $this;
	}

	/**
	 * Parse a list of relations into individuals.
	 *
	 * @param   array  $relations relations that should be eager loaded
	 * @return array
	 */
	protected function parseWithRelations(array $relations)
	{
		$results = array();

		foreach ($relations as $name => $constraints)
		{
			/** If the "relation" value is actually a numeric key, we can assume that no
			 * constraints have been specified for the eager load and we'll just put
			 * an empty Closure with the loader so that we can treat all the same.
			 */
			if (is_numeric($name))
			{
				$name = $constraints;

				// TODO do we want createSelectWithConstraint? e.g to not load the whole related Model object
				$constraints = function ()
				{

					// Empty callback
				};
			}

			/** We need to separate out any nested includes. Which allows the developers
			 * to load deep relations using "dots" without stating each level of
			 * the relation with its own key in the array of eager load names.
			 */
			$results = $this->addNestedWiths($name, $results);

			$results[$name] = $constraints;
		}

		return $results;
	}

	/**
	 * Parse the nested relations in a relation.
	 *
	 * @param   string  $name    relation name
	 * @param   array   $results eager loaded relations so far
	 * @return array
	 */
	protected function addNestedWiths($name, $results)
	{
		$progress = array();

		/** If the relation has already been set on the result array, we will not set it
		 * again, since that would override any constraints that were already placed
		 * on the relations. We will only set the ones that are not specified.
		 */
		foreach (explode('.', $name) as $segment)
		{
			$progress[] = $segment;

			if (! isset($results[$last = implode('.', $progress)]))
			{
				$results[$last] = function ()
				{

					// Empty callback
				};
			}
		}

		return $results;
	}

	/**
	 * Eager load the relations for the models.
	 *
	 * @param   array  $models eager load the realtion on the specified models
	 * @return array
	 */
	public function eagerLoadRelations(array $models)
	{
		foreach ($this->eagerLoad as $name => $constraints)
		{
			/** For nested eager loads we'll skip loading them here and they will be set as an
			 * eager load on the query to retrieve the relation so that they will be eager
			 * loaded on that query, because that is where they get hydrated as models.
			 */
			if (!StringHelper::contains($name, '.'))
			{
				$models = $this->eagerLoadRelation($models, $name, $constraints);
			}
		}

		return $models;
	}

	/**
	 * Eagerly load the relation on a set of models.
	 *
	 * @param   array     $models      ?
	 * @param   string    $name        ?
	 * @param   Closure   $constraints ?
	 * @return array
	 */
	protected function eagerLoadRelation(array $models, $name, Closure $constraints)
	{
		/** First we will "back up" the existing where conditions on the query so we can
		 * add our eager constraints. Then we will merge the wheres that were on the
		 * query back to it in order that any where conditions might be specified.
		 */
		$relation = $this->getRelation($name);

		$relation->addEagerConstraints($models);

		$constraints($relation);

		/** Once we have the results, we just match those back up to their parent models
		 * using the relation instance. Then we just return the finished arrays
		 * of models which have been eagerly hydrated and are readied for return.
		 */
		return $relation->match(
			$relation->initRelation($models, $name),
			$relation->getEager(), $name
		);
	}

	/**
	 * Get the relation instance for the given relation name.
	 *
	 * @param   string  $name relation name
	 * @return Relation
	 */
	public function getRelation($name)
	{
		/**
		 * We want to run a relation query without any constrains so that we will
		 * not have to remove these where clauses manually which gets really hacky
		 * and error prone. We don't want constraints because we add eager ones.
		 */
		$relation = Relation::noConstraints(
			function () use ($name)
			{
				try
				{
					return $this->getModel()->{$name}();
				}
				catch (BadMethodCallException $e)
				{
					throw RelationNotFoundException::make($this->getModel(), $name);
				}
			}
		);

		$nested = $this->relationsNestedUnder($name);

		/** If there are nested relations set on the query, we will put those onto
		 * the query instances so that they can be handled after this relation
		 * is loaded. In this way they will all trickle down as they are loaded.
		 */
		if (count($nested) > 0)
		{
			$relation->getQuery()->with($nested);
		}

		return $relation;
	}

	/**
	 * Get the deeply nested relations for a given top-level relation.
	 *
	 * @param   string  $relation relation to be checked for nester relations
	 * @return array
	 */
	protected function relationsNestedUnder($relation)
	{
		$nested = array();

		/** We are basically looking for any relations that are nested deeper than
		 * the given top-level relation. We will just check for any relations
		 * that start with the given top relations and adds them to our arrays.
		 */
		foreach ($this->eagerLoad as $name => $constraints)
		{
			if ($this->isNestedUnder($relation, $name))
			{
				$nested[substr($name, strlen($relation . '.'))] = $constraints;
			}
		}

		return $nested;
	}

	/**
	 * Determine if the relation is nested.
	 *
	 * @param   string  $relation relation
	 * @param   string  $name     name of relation that is to be checked to be nested
	 * @return boolean
	 */
	protected function isNestedUnder($relation, $name)
	{
		return StringHelper::contains($name, '.') && StringHelper::startWith($name, $relation . '.');
	}

	/**
	 * Execute the query and get the first result.
	 *
	 * @param   array  $columns columns to be selected
	 * @return Model|object|static|null
	 */
	public function first($columns = array('*'))
	{
		return $this->setLimit(1)->get($columns)->first();
	}


	/**
	 * Add a WHERE IN statement to the query
	 *
	 * @param   string $keyName   key name for the where clause
	 * @param   array  $keyValues array of values to be matched
	 * @return void
	 */
	public function whereIn($keyName, $keyValues)
	{
		$this->query->where(
			$keyName . ' IN (' . implode(', ', $keyValues) . ')'
		);
	}
}
