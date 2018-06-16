<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\ModelHelpers;

use Joomla\Entity\Query;
use \Joomla\Entity\Relations\HasOne;
use \Joomla\Entity\Model;
use \Joomla\Entity\Relations\HasMany;
use \Joomla\Entity\Helpers\Collection;
use Joomla\Entity\Relations\Relation;
use Joomla\String\Inflector;

/**
 * Trait Relations
 * @package Joomla\Entity\Helpers
 * @since 1.0
 */
trait Relations
{
	/**
	 * The loaded relations for the model.
	 *
	 * @var array
	 */
	protected $relations = [];

	/**
	 * The relations that should be touched on save.
	 *
	 * @var array
	 */
	protected $touches = [];

	/**
	 * Determine if the model touches a given relation.
	 *
	 * @param   string  $relation relation name
	 * @return boolean
	 */
	public function touches($relation)
	{
		return in_array($relation, $this->touches);
	}

	/**
	 * Touch the owning relations of the model.
	 *
	 * @return void
	 */
	public function touchOwners()
	{
		foreach ($this->touches as $relation)
		{
			$this->$relation()->touch();

			if ($this->$relation instanceof self)
			{
				$this->$relation->touchOwners();
			}
			elseif ($this->$relation instanceof Collection)
			{
				$this->$relation->each(
					function (Model $relation)
					{
						$relation->touchOwners();
					}
				);
			}
		}
	}

	/**
	 * Create a new model instance for a related model.
	 *
	 * @param   string  $class Model class name
	 * @return mixed
	 */
	protected function newRelatedInstance($class)
	{
		return new $class($this->getDb());
	}

	/**
	 * Get all the loaded relations for the instance.
	 *
	 * @return array
	 */
	public function getRelations()
	{
		return $this->relations;
	}

	/**
	 * Get all the loaded relations for the instance.
	 * Relations are serialised (array format).
	 *
	 * @return array
	 */
	public function getRelationsAsArray()
	{
		$relations = [];

		foreach ($this->getRelations() as $key => $value)
		{
			/** First, we try need to check for the instance to be converted
			 * to be of a supported type, Model or Collection. Then, we go ahead
			 * and convert it to array.
			 */
			if ($value instanceof Model || $value instanceof Collection)
			{
				$relation = $value->toArray();
			}

			/** If the value is null, we'll still go ahead and set it in this list of
			 * attributes since null is used to represent empty relationships if
			 * if it a has one or belongs to type relationships on the models.
			 */
			elseif (is_null($value))
			{
				$relation = $value;
			}

			/** If the relation value has been set, we will set it on this attributes
			 * list for returning. If its not a Model, Collection or null, we'll not set
			 * the value on the array because it is some type of invalid value.
			 */
			if (isset($relation) || is_null($value))
			{
				$relations[$key] = $relation;
			}

			unset($relation);
		}

		return $relations;
	}

	/**
	 * Get a specified relation.
	 *
	 * @param   string  $relation relation name
	 * @return mixed
	 */
	public function getRelation($relation)
	{
		return $this->relations[$relation];
	}

	/**
	 * Determine if the given relation is loaded.
	 *
	 * @param   string  $key relation name
	 * @return boolean
	 */
	public function relationLoaded($key)
	{
		return array_key_exists($key, $this->relations);
	}

	/**
	 * Set the specific relation in the model.
	 *
	 * @param   string $relation relation name
	 * @param   mixed  $value    Relation instance
	 * @return $this
	 */
	public function setRelation($relation, $value)
	{
		$this->relations[$relation] = $value;

		return $this;
	}

	/**
	 * Get the relations that are touched on save.
	 *
	 * @return array
	 */
	public function getTouchedRelations()
	{
		return $this->touches;
	}

	/**
	 * Define a one-to-one relation.
	 *
	 * @param   string  $related    related Model
	 * @param   string  $foreignKey foreign key name in current mode
	 * @param   string  $localKey   local primary key name
	 * @return \Joomla\Entity\Relations\HasOne
	 */
	public function hasOne($related, $foreignKey = null, $localKey = null)
	{
		$instance = $this->newRelatedInstance($related);

		$foreignKey = $foreignKey ?: Inflector::singularize($this->table) . '_id';

		$localKey = $localKey ?: $this->getPrimaryKey();

		return $this->newHasOne($instance->newQuery(), $this, $instance->getTable() . '.' . $foreignKey, $localKey);
	}

	/**
	 * Instantiate a new HasOne relation.
	 *
	 * @param   Query   $query      just a query instance
	 * @param   Model   $parent     $this model instance
	 * @param   string  $foreignKey foreign key name in current mode
	 * @param   string  $localKey   local primary key name
	 * @return HasOne
	 */
	protected function newHasOne($query, $parent, $foreignKey, $localKey)
	{
		return new HasOne($query, $parent, $foreignKey, $localKey);
	}

	/**
	 * Define a one-to-many relation.
	 *
	 * @param   string  $related    related Model
	 * @param   string  $foreignKey foreign key name in current mode
	 * @param   string  $localKey   local primary key name
	 * @return \Joomla\Entity\Relations\HasMany
	 */
	public function hasMany($related, $foreignKey = null, $localKey = null)
	{
		$instance = $this->newRelatedInstance($related);

		$foreignKey = $foreignKey ?: Inflector::singularize($this->table) . '_id';

		$localKey = $localKey ?: $this->getPrimaryKey();

		return $this->newHasMany($instance->newQuery(), $this, $instance->getTable() . '.' . $foreignKey, $localKey);
	}

	/**
	 * Instantiate a new HasMany relation.
	 *
	 * @param   Query   $query      just a query instance
	 * @param   Model   $parent     $this model instance
	 * @param   string  $foreignKey foreign key name in current mode
	 * @param   string  $localKey   local primary key name
	 * @return \Joomla\Entity\Relations\HasMany
	 */
	protected function newHasMany($query, $parent, $foreignKey, $localKey)
	{
		return new HasMany($query, $parent, $foreignKey, $localKey);
	}
}
