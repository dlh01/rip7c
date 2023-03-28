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

class IntegerLiteral implements Integer
{
    public function __construct(private int $origin)
    {
    }

    public function integer(): int
    {
        return $this->origin;
    }
}
