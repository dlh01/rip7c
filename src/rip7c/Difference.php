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

final class Difference implements Integer
{
    /**
     * @var Integer[]
     */
    private array $integers;

    public function __construct(Integer ...$integers)
    {
        $this->integers = $integers;
    }

    public function integer(): int
    {
        return array_reduce(
            \array_slice($this->integers, 1),
            fn (int $carry, Integer $value) => $carry - $value->integer(),
            $this->integers[0]->integer(),
        );
    }
}
