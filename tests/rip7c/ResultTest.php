<?php

/*
 * This file is part of dlh01/rip7c.
 *
 * (c) David Herrera <mail@dlh01.info>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace rip7c;

use Alley\Validator\AlwaysValid;
use Alley\Validator\Not;

final class ResultTest extends \PHPUnit\Framework\TestCase
{
    public function testIsTrue()
    {
        $expected = 'foo';
        $result = Result::create(new AlwaysValid(), 'bar');
        $this->assertSame($expected, $result->isTrue(fn () => $expected, 'baz'));
    }

    public function testIsFalse()
    {
        $expected = 'foo';
        $result = Result::create(new Not(new AlwaysValid(), 'test'), 'bar');
        $this->assertSame($expected, $result->isTrue(fn () => 'baz', $expected));
    }
}
