<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\ModelHelpers;

use \Joomla\Entity\Relations\HasOne;
use \Joomla\Entity\Helpers\Collection;
use Joomla\String\Inflector;
use Joomla\String\Normalise;

/**
 * Trait HasRelationships
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
	protected $relations = array();

	/**
	 * The relations that should be touched on save.
	 *
	 * @var array
	 */
	protected $touches = array();

	/**
	 * Determine if the model touches a given relation.
	 *
	 * @param   string  $relation ?
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
	 * @param   string  $class ?
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
	 * Get a specified relationship.
	 *
	 * @param   string  $relation ?
	 * @return mixed
	 */
	public function getRelation($relation)
	{
		return $this->relations[$relation];
	}

	/**
	 * Determine if the given relation is loaded.
	 *
	 * @param   string  $key ?
	 * @return boolean
	 */
	public function relationLoaded($key)
	{
		return array_key_exists($key, $this->relations);
	}

	/**
	 * Set the specific relationship in the model.
	 *
	 * @param   string  $relation ?
	 * @param   mixed   $value    ?
	 * @return $this
	 */
	public function setRelation($relation, $value)
	{
		$this->relations[$relation] = $value;

		return $this;
	}

	/**
	 * Set the entire relations array on the model.
	 *
	 * @param   array  $relations ?
	 * @return $this
	 */
	public function setRelations(array $relations)
	{
		$this->relations = $relations;

		return $this;
	}

	/**
	 * Get the relationships that are touched on save.
	 *
	 * @return array
	 */
	public function getTouchedRelations()
	{
		return $this->touches;
	}

	/**
	 * Set the relationships that are touched on save.
	 *
	 * @param   array  $touches ?
	 * @return $this
	 */
	public function setTouchedRelations(array $touches)
	{
		$this->touches = $touches;

		return $this;
	}

	/**
	 * Define a one-to-one relationship.
	 *
	 * @param   string  $related    ?
	 * @param   string  $foreignKey ?
	 * @param   string  $localKey   ?
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
	 * Instantiate a new HasOne relationship.
	 *
	 * @param   \Joomla\Entity\Query $query      ?
	 * @param   \Joomla\Entity\Model $parent     ?
	 * @param   string               $foreignKey ?
	 * @param   string               $localKey   ?
	 * @return HasOne
	 */
	protected function newHasOne($query, $parent, $foreignKey, $localKey)
	{
		return new HasOne($query, $parent, $foreignKey, $localKey);
	}
}