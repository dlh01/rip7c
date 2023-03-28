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

require_once dirname(__DIR__) . '/vendor/autoload.php';

$origin_cache_control = 's-maxage=86400, max-age=86400, must-revalidate, no-store';
$origin_age           = 1138;

$cc_not_empty = Result::create(
    new FastFailValidatorChain([
        new Type(['type' => 'string']),
        new Comparison(['operator' => '!==', 'compared' => '']),
    ]),
    $origin_cache_control,
);
$age_numeric  = Result::create(new Type(['type' => 'numeric']), $origin_age);

$ttl = new Max(
    new Difference(
        $cc_not_empty->isTrue(
            fn () => new MaxAge($origin_cache_control),
            new IntegerLiteral(0),
        ),
        $age_numeric->isTrue(
            fn () => new IntegerLiteral($origin_age),
            new IntegerLiteral(0),
        )
    ),
    new IntegerLiteral(3600),
);

echo "Cache TTL is " . $ttl->integer() . PHP_EOL;
exit;
