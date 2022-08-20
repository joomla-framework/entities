<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Tests\Helpers;

use Joomla\Entity\Helpers\StringHelper;
use PHPUnit\Framework\TestCase;

/**
 *
 * @since  1.0
 */
class StringHelperTest extends TestCase
{
	/**
	 * @covers \Joomla\Entity\Helpers\StringHelper::contains()
	 * @return void
	 */
	public function testContains()
	{
		$str = "Joomla Entities is the coolest GSoC Project";
		$needle = 'GSoC';

		$this->assertTrue(StringHelper::contains($str, $needle),
			"Test contains method, true."
		);
	}

	/**
	 * @covers \Joomla\Entity\Helpers\StringHelper::startWith()
	 * @return void
	 */
	public function testStartWith()
	{
		$str = "Joomla Entities is the coolest GSoC Project";
		$needle = 'Joomla';

		$this->assertTrue(StringHelper::startWith($str, $needle),
			"Test startWith method, true."
		);

		$needle = 'NotJoomla';
		$this->assertFalse(StringHelper::startWith($str, $needle),
			"Test startWith method, false."
		);
	}
}

