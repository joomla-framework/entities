<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */


namespace Joomla\Entity\Relations;

use Joomla\Entity\Helpers\Collection;


/**
 * Class HasMany
 * @package Joomla\Entity\Relations
 * @since   1.0
 */
class HasMany extends HasOneOrMany
{
	/**
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	public function getResults()
	{
		return $this->query->get();
	}

	/**
	 * Initialize the relation on a set of models.
	 *
	 * @param   array   $models   ?
	 * @param   string  $relation ?
	 * @return array
	 */
	public function initRelation(array $models, $relation)
	{
		foreach ($models as $model)
		{
			$model->setRelation($relation, new Collection);
		}

		return $models;
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param   array       $models   ?
	 * @param   Collection  $results  ?
	 * @param   string      $relation ?
	 * @return array
	 */
	public function match(array $models, Collection $results, $relation)
	{
		return $this->matchMany($models, $results, $relation);
	}
}
