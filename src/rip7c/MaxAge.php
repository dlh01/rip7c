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

use Stringable;

use function Alley\traverse;

final class MaxAge implements Integer
{
    public function __construct(private string|Stringable $header)
    {
    }

    public function integer(): int
    {
        $out = 0;

        foreach (explode(',', (string)$this->header) as $directive) {
            $directive = trim($directive);

            [$name, $value] = traverse(explode('=', $directive), ['0', '1']);

            if (null !== $value && 'max-age' === $name) {
                $out = (int)$value;
                break;
            }
        }

        return $out;
    }
}
