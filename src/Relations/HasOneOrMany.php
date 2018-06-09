<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Relations;

use Joomla\Entity\Model;
use Joomla\Entity\Query;
use Joomla\Entity\Helpers\Collection;

/**
 * Class HasOneOrMany
 * @package Joomla\Entity\Relations
 * @since   1.0
 */
abstract class HasOneOrMany extends Relation
{
	/**
	 * The foreign key of the parent model.
	 *
	 * @var string
	 */
	protected $foreignKey;

	/**
	 * The local key of the parent model.
	 *
	 * @var string
	 */
	protected $localKey;

	/**
	 * The count of self joins.
	 *
	 * @var integer
	 */
	protected static $selfJoinCount = 0;

	/**
	 * Create a new has one or many relationship instance.
	 *
	 * @param   Query   $query      ?
	 * @param   Model   $parent     ?
	 * @param   string  $foreignKey ?
	 * @param   string  $localKey   ?
	 */
	public function __construct(Query $query, Model $parent, $foreignKey, $localKey)
	{
		$this->localKey = $localKey;
		$this->foreignKey = $foreignKey;

		parent::__construct($query, $parent);
	}

	/**
	 * Set the base constraints on the relation query.
	 *
	 * @return void
	 */
	public function addConstraints()
	{
		if (static::$constraints)
		{
			$this->query->where($this->foreignKey . ' = ' . $this->getParentKey());

			$this->query->whereNotNull($this->foreignKey);
		}
	}

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param   array  $models ?
	 * @return void
	 */
	public function addEagerConstraints(array $models)
	{

		// TODO there may be a problem with the where in, a more complicated logic for values and strings may be needed.
		// Also, this may be included in the Query, as it may be needed somewhere else.
		$this->query->where(
			$this->foreignKey . ' IN (' . implode(',', $this->getKeys($models, $this->localKey)) . ')'
		);
	}

	/**
	 * Match the eagerly loaded results to their single parents.
	 *
	 * @param   array       $models   ?
	 * @param   Collection  $results  ?
	 * @param   string      $relation ?
	 * @return array
	 */
	public function matchOne(array $models, Collection $results, $relation)
	{
		return $this->matchOneOrMany($models, $results, $relation, 'one');
	}

	/**
	 * Match the eagerly loaded results to their many parents.
	 *
	 * @param   array       $models   ?
	 * @param   Collection  $results  ?
	 * @param   string      $relation ?
	 * @return array
	 */
	public function matchMany(array $models, Collection $results, $relation)
	{
		return $this->matchOneOrMany($models, $results, $relation, 'many');
	}

	/**
	 * Match the eagerly loaded results to their many parents.
	 *
	 * @param   array       $models   ?
	 * @param   Collection  $results  ?
	 * @param   string      $relation ?
	 * @param   string      $type     ?
	 * @return array
	 */
	protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
	{
		$dictionary = $this->buildDictionary($results);

		/** Once we have the dictionary we can simply spin through the parent models to
		 * link them up with their children using the keyed dictionary to make the
		 * matching very convenient and easy work. Then we'll just return them.
		 */
		foreach ($models as $model)
		{
			if (isset($dictionary[$key = $model->getAttribute($this->localKey)]))
			{
				$value = $type == 'one' ? reset($dictionary[$key]) : new Collection($dictionary[$key]);

				$model->setRelation($relation, $value);
			}
		}

		return $models;
	}

	/**
	 * Build model dictionary keyed by the relation's foreign key.
	 *
	 * @param   Collection  $results ?
	 * @return array
	 */
	protected function buildDictionary(Collection $results)
	{
		$foreign = $this->getForeignKeyName();

		$dictionary = array();

		foreach ($results as $key => $value)
		{
			if (! isset($dictionary[$key]))
			{
				$dictionary[$key] = array();
			}

			$dictionary[$key][] = array($value->{$foreign} => $value);
		}

		return $dictionary;
	}

	/**
	 * Find a model by its primary key or return new instance of the related model.
	 *
	 * @param   mixed  $id      ?
	 * @param   array  $columns ?
	 * @return Collection|Model
	 */
	public function findOrNew($id, $columns = array('*'))
	{
		if (is_null($instance = $this->find($id, $columns)))
		{
			$instance = $this->related->newInstance($this->getDb());

			$this->setForeignAttributesForCreate($instance);
		}

		return $instance;
	}

	/**
	 * Get the first related model record matching the attributes or instantiate it.
	 *
	 * @param   array  $attributes ?
	 * @param   array  $values     ?
	 * @return Model
	 */
	public function firstOrNew(array $attributes, array $values = array())
	{
		if (is_null($instance = $this->where($attributes)->first()))
		{
			$instance = $this->related->newInstance($this->getDb(), $attributes + $values);

			$this->setForeignAttributesForCreate($instance);
		}

		return $instance;
	}

	/**
	 * Get the first related record matching the attributes or create it.
	 *
	 * @param   array  $attributes ?
	 * @param   array  $values     ?
	 * @return Model
	 */
	public function firstOrCreate(array $attributes, array $values = array())
	{
		if (is_null($instance = $this->where($attributes)->first()))
		{
			$instance = $this->create($attributes + $values);
		}

		return $instance;
	}

	/**
	 * Create or update a related record matching the attributes, and fill it with values.
	 *
	 * @param   array  $attributes ?
	 * @param   array  $values     ?
	 * @return Model
	 */
	public function updateOrCreate(array $attributes, array $values = array())
	{
		$instance = $this->firstOrNew($attributes);

		$instance->setAttributes(array_combine($attributes, $values));

		$instance->save();

		return $instance;
	}

	/**
	 * Attach a model instance to the parent model.
	 *
	 * @param   Model  $model ?
	 * @return Model|false
	 */
	public function save(Model $model)
	{
		$this->setForeignAttributesForCreate($model);

		return $model->save() ? $model : false;
	}

	/**
	 * Attach a collection of models to the parent instance.
	 *
	 * @param   \Traversable|array  $models ?
	 * @return \Traversable|array
	 */
	public function saveMany($models)
	{
		foreach ($models as $model)
		{
			$this->save($model);
		}

		return $models;
	}

	/**
	 * Create a new instance of the related model.
	 *
	 * @param   array  $attributes attributes
	 * @return Model
	 */
	public function create($attributes = array())
	{
		$instance = $this->related->newInstance($this->getDb(), $attributes);

		$this->setForeignAttributesForCreate($instance);

		$instance->save();

		return $instance;
	}

	/**
	 * Create a Collection of new instances of the related model.
	 *
	 * @param   array  $records ?
	 * @return Collection
	 */
	public function createMany(array $records)
	{
		$instances = new Collection;

		foreach ($records as $record)
		{
			$instances->add($this->create($record));
		}

		return $instances;
	}

	/**
	 * Set the foreign ID for creating a related model.
	 *
	 * @param   Model  $model ?
	 * @return void
	 */
	protected function setForeignAttributesForCreate(Model $model)
	{
		$model->setAttribute($this->getForeignKeyName(), $this->getParentKey());
	}

	/**
	 * Perform an update on all the related models.
	 *
	 * @param   array  $attributes ?
	 * @return integer
	 */
	public function updateRelated(array $attributes)
	{
		if ($this->related->usesTimestamps() && $this->related->getColumnAlias("updatedAt"))
		{
			$this->related->updatedAt = $this->related->freshTimestampString();
		}

		return $this->related->update($attributes);
	}

	/**
	 * Get the key value of the parent's local key.
	 *
	 * @return mixed
	 */
	public function getParentKey()
	{
		return $this->parent->getAttribute($this->localKey);
	}

	/**
	 * Get the fully qualified parent key name.
	 *
	 * @return string
	 */
	public function getFullParentKeyName()
	{
		return $this->parent->getFullAttributeName($this->localKey);
	}

	/**
	 * Get the plain foreign key.
	 *
	 * @return string
	 */
	public function getForeignKeyName()
	{
		$segments = explode('.', $this->foreignKey);

		return end($segments);
	}
}
