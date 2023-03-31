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

use Alley\Validator\Comparison;
use Alley\Validator\FastFailValidatorChain;
use Alley\Validator\Type;
use rip7c\Difference;
use rip7c\IntegerLiteral;
use rip7c\Max;
use rip7c\MaxAge;
use rip7c\Result;

require_once \dirname(__DIR__) . '/vendor/autoload.php';

$origin_cache_control = 's-maxage=86400, max-age=86400, must-revalidate, no-store';
$origin_age           = 1138;

$ttl = new Max(
    new IntegerLiteral(3600),
    new Difference(
        Result::of(
            $origin_cache_control,
            new FastFailValidatorChain([
                new Type(['type' => 'string']),
                new Comparison(['operator' => '!==', 'compared' => '']),
            ])
        )->isTrue(
            fn () => new MaxAge($origin_cache_control),
            new IntegerLiteral(0),
        ),
        new IntegerLiteral(
            Result::of(
                $origin_age,
                new Type(['type' => 'numeric']),
            )->isTrue(
                fn () => $origin_age,
                0,
            ),
        ),
    ),
);

$out = new Concat(
    new StringLiteral('Cache TTL is '),
    new IntegerPrinted($ttl),
    new StringLiteral(\PHP_EOL),
);
$out->print(STDOUT);  // "Cache TTL is 85262"

exit;
