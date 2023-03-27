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
        ValidatorInterface $test,
        callable $value,
    ) {
        $this->test  = $test;
        $this->value = $value;
    }

    public static function create(ValidatorInterface $test, $value): Result
    {
        $cls = fn () => $value;
        $rst = new self(new Type(['type' => 'callable']), $cls);

        return new self($test, $rst->isTrue($cls, $cls));
    }

    public function isTrue(callable $then, $else)
    {
        return $this->test->isValid(($this->value)()) ? $then() : $else;
    }

    public function isFalse(callable $then, $else)
    {
        return $this->test->isValid(($this->value)()) ? $else : $then();
    }
}
