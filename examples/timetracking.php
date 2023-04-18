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

use Alley\Validator\Type;
use rip7c\Result;

require_once \dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('America/New_York');

$sprintStart = new DateTimeImmutable('last wednesday');
$filename    = $argv[1];
$file        = fopen($filename, 'r');

$opened = Result::of($file, new Type(['type' => 'resource']));
$opened->isFalse(fn() => throw new Exception("File at {$filename} does not exist"), '');

$date     = null;
$type     = null;
$client   = null;
$start    = null;
$stop     = null;
$duration = null;
$seconds  = 0;;

$rows        = [];
$sums        = [];
$commitments = ['TOTAL' => (32 * 3600)];

while (($line = fgets($file)) !== false) {
    $pieces = explode(' ', $line);
    $pieces = array_values(array_filter($pieces, fn($p) => $p !== '-' && $p !== $date));
    $pieces = array_map('trim', $pieces);
    $rows[] = $pieces;
}

while (($row = current($rows)) !== false) {
    $date   = '.' === $row[0] ? $date : $row[0];
    $type   = '.' === $row[1] ? $type : $row[1];
    $client = '.' === $row[2] ? $client : $row[2];

    if ($date >= $sprintStart->format('Y-m-d')) {
        if ('COMMIT' === $type) {
            preg_match('/^\d+/', $row[3], $match);

            $commitments[$client] ??= 0;
            $commitments[$client] += (int)$match[0] * 3600;
        }

        if ('COMMIT' !== $type) {
            $sums[$client] ??= 0;

            $start = $row[3];

            if (str_ends_with($start, 'h')) {
                $seconds  = ((float)rtrim($start, 'h')) * 3600;
                $hours    = floor($seconds / 3600);
                $minutes  = floor(($seconds / 60) % 60);
                $duration = $hours . ":" . str_pad((string)$minutes, 2, "0", STR_PAD_LEFT);
                echo implode(' | ', [$date, $type, $client, ' - ', ' - ', $duration]);
                $sums[$client] += $seconds;
            }

            if ( ! str_ends_with($start, 'h')) {
                $prev = 0;
                while ($start === '.') {
                    prev($rows);
                    $start = current($rows)[4];
                    $prev++;
                }

                while ($prev > 0) {
                    next($rows);
                    $prev--;
                }

                $stop = $row[4] ?? date('g:ia');
                $prev = 0;
                while ($stop === '.') {
                    prev($rows);
                    $stop = current($rows)[3];
                    $prev++;
                }

                while ($prev > 0) {
                    next($rows);
                    $prev--;
                }

                if ($stop === '..') {
                    next($rows);
                    $stop = current($rows)[3];
                    prev($rows);
                }

                $start = rtrim($start, 'm') . 'm';
                $stop  = rtrim($stop, 'm') . 'm';

                $seconds = strtotime($stop) - strtotime($start);
                if (str_ends_with($start, 'pm') && str_ends_with($stop, 'am')) {
                    $seconds += 86400;
                }
                $hours    = floor($seconds / 3600);
                $minutes  = floor(($seconds / 60) % 60);
                $duration = $hours . ":" . str_pad((string)$minutes, 2, "0", STR_PAD_LEFT);

                echo implode(' | ', [$date, $type, $client, $start, $stop, $duration]);

                $sums[$client] += $seconds;
            }

            echo "\n";
        }
    }

    next($rows);
}

echo "\n";
echo "TRACKED:";
echo "\n";
$sums['TOTAL'] = array_sum($sums);
foreach ($sums as $client => $seconds) {
    $hours    = floor($seconds / 3600);
    $minutes  = floor(($seconds / 60) % 60);
    $duration = $hours . ":" . str_pad((string)$minutes, 2, "0", STR_PAD_LEFT);

    echo "{$duration} {$client}";
    echo "\n";
}
echo "\n";

echo "COMMITMENTS:";
echo "\n";
foreach ($commitments as $client => $seconds) {
    $hours    = floor($seconds / 3600);
    $minutes  = floor(($seconds / 60) % 60);
    $duration = $hours . ":" . str_pad((string)$minutes, 2, "0", STR_PAD_LEFT);

    echo "{$duration} {$client}";
    echo "\n";
}
echo "\n";

echo "REMAINING:";
echo "\n";
foreach ($commitments as $client => $seconds) {
    $remaining = $seconds - ($sums[$client] ?? 0);
    $hours     = floor($remaining / 3600);
    $minutes   = floor(($remaining / 60) % 60);
    $duration  = $hours . ":" . str_pad((string)$minutes, 2, "0", STR_PAD_LEFT);

    echo "{$duration} {$client}";
    echo "\n";
}
echo "\n";

echo "CAPACITY:";
echo "\n";
echo ceil(($sprintStart->add(new DateInterval('P7D'))->getTimestamp() - time()) / 86400) . ' days';
echo "\n";

fclose($file);
