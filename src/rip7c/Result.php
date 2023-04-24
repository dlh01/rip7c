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

use Alley\Validator\Type;
use Laminas\Validator\ValidatorInterface;

final class Result implements Boolean
{
    private ValidatorInterface $test;

    /**
     * @var callable
     */
    private $value;

    public function __construct(
        callable $value,
        ValidatorInterface $test,
    ) {
        $this->test  = $test;
        $this->value = $value;
    }

    public static function of($value, ValidatorInterface $test): Result
    {
        $cls = fn () => $value;
        $rst = new self($cls, new Type(['type' => 'callable']));

        return new self($rst->isTrue($value, fn () => $cls), $test);
    }

    public function isTrue($then, $else)
    {
        $out = $this->test->isValid(($this->value)()) ? $then : $else;
        return is_callable($out) ? $out() : $out;
    }

    public function isFalse($then, $else)
    {
        return $this->isTrue($else, $then);
    }
}
