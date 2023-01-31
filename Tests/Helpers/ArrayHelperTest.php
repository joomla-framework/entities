<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Tests\Helpers;

use Joomla\Entity\Helpers\ArrayHelper;
use PHPUnit\Framework\TestCase;

/**
 *
 * @since  1.0
 */
class ArrayHelperTest extends TestCase
{
    /**
     * @covers \Joomla\Entity\Helpers\ArrayHelper::set()
     * @return void
     */
    public function testAll()
    {
        $array = [];
        $key   = 'test->set->method';
        $value = 'works';

        ArrayHelper::set($array, $key, $value);

        $this->assertEquals(
            $array['test']['set']['method'],
            'works',
            "Test set for test->set->method key."
        );
    }
}
