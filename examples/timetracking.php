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
use Alley\Validator\Type;
use rip7c\Result;

require_once \dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('America/New_York');

$now = new DateTimeImmutable('now');
$isWednesday = Result::of(
    $now->format('l'),
    new Comparison(['operator' => '===', 'compared' => 'Wednesday'])
);
$sprintStart = $isWednesday->isTrue(fn() => $now, new DateTimeImmutable('last wednesday'));
$sprintStartYmd = $sprintStart->format('Y-m-d');
$filename = $argv[1];
$file = fopen($filename, 'r');

$opened = Result::of($file, new Type(['type' => 'resource']));
$opened->isFalse(fn() => throw new Exception("File at {$filename} does not exist"), '');

$backwards = abs((int) ($argv[2] ?? 0));
if ($backwards > 0) {
    $sprintStart = $sprintStart->sub(new DateInterval("P{$backwards}W"));
    $sprintStartYmd = $sprintStart->format('Y-m-d');
    $now = $sprintStart->add(new DateInterval('P1W'));
}

$date = null;
$type = null;
$client = null;
$start = null;
$stop = null;
$duration = null;
$seconds = 0;;

$rows = [];
$sums = [];
$byDay = [];
$commitments = [];

while (($line = fgets($file)) !== false) {
    if (trim($line) === '' || str_starts_with($line, '#')) {
        continue;
    }
    $line = preg_replace('/ ([AP]M)/', '\1', $line);
    $line = preg_replace('/^\.\. /', '. . ', $line);
    $line = preg_replace('/^\.\.\. /', '. . . ', $line);
    $pieces = explode(' ', $line);
    $pieces = array_values(array_filter($pieces, fn($p) => $p !== '-' && $p !== $date));
    $pieces = array_map('trim', $pieces);
    $rows[] = $pieces;
}

echo ">>> LOGS";
echo "\n";

while (($row = current($rows)) !== false) {
    $date = '.' === $row[0] ? $date : $row[0];
    $type = strtoupper('.' === $row[1] ? $type : $row[1]);
    $client = strtoupper('.' === $row[2] ? $client : $row[2]);

    if ($date >= $sprintStartYmd) {
        if ('COMMIT' === $type) {
            preg_match('/^\d+/', $row[3], $match);

            $commitments[$client] ??= 0;
            $commitments[$client] += (int)$match[0] * 3600;
        }

        if ('COMMIT' !== $type) {
            $sums[$client] ??= 0;
            $byDay[$date][$client] ??= 0;

            $start = $row[3];

            if (str_ends_with($start, 'h')) {
                $seconds = ((float)rtrim($start, 'h')) * 3600;
                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds / 60) % 60);
                $duration = $hours . ":" . str_pad((string)$minutes, 2, "0", STR_PAD_LEFT);
                echo implode(' | ', array_map(fn($s) => str_pad($s, 10), [$date, $type, $client, '-', '-', $duration]));
                $sums[$client] += $seconds;
            }

            if (preg_match('/^\d+m$/', $start)) {
                $seconds = ((float) rtrim($start, 'm')) * 60;
                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds / 60) % 60);
                $duration = $hours . ":" . str_pad((string)$minutes, 2, "0", STR_PAD_LEFT);
                echo implode(' | ', array_map(fn($s) => str_pad($s, 10), [$date, $type, $client, '-', '-', $duration]));
                $sums[$client] += $seconds;
            }

            if (!str_ends_with($start, 'h') && !preg_match('/^\d+m$/', $start)) {
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

                if ($stop === '>') {
                    next($rows);
                    $stop = current($rows)[3];
                    prev($rows);
                }

                $start = rtrim(strtolower($start), 'm') . 'm';
                $stop = rtrim(strtolower($stop), 'm') . 'm';

                $seconds = strtotime($stop) - strtotime($start);
                if (str_ends_with(strtolower($start), 'pm') && str_ends_with(strtolower($stop), 'am')) {
                    $seconds += 86400;
                }
                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds / 60) % 60);
                $duration = $hours . ":" . str_pad((string)$minutes, 2, "0", STR_PAD_LEFT);

                echo implode(
                    ' | ',
                    array_map(fn($s) => str_pad($s, 10), [$date, $type, $client, $start, $stop, $duration])
                );

                $sums[$client] += $seconds;
                $byDay[$date][$client] += $seconds;
            }

            echo "\n";
        }
    }

    next($rows);
}

ksort($commitments);
$commitments['TOTAL'] = 32 * 3600;

echo "\n";
echo ">>> COMMITMENTS";
echo "\n";
foreach ($commitments as $client => $seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds / 60) % 60);
    $duration = $hours . ":" . str_pad((string)$minutes, 2, "0", STR_PAD_LEFT);

    echo "{$duration} {$client}";
    echo "\n";
}
echo "\n";

echo ">>> TRACKED";
echo "\n";
ksort($sums);
$sums['TOTAL'] = array_sum($sums);
foreach ($sums as $client => $seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds / 60) % 60);
    $duration = $hours . ":" . str_pad((string)$minutes, 2, "0", STR_PAD_LEFT);

    echo "{$duration} {$client}";
    echo "\n";
}
echo "\n";

echo ">>> BY DAY";
echo "\n";
foreach ($byDay as $date => $clients) {
    echo '>> ' . strtoupper(date_create_immutable($date)->format('D d'));
    echo "\n";
    foreach ($clients as $client => $seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $duration = $hours . ":" . str_pad((string)$minutes, 2, "0", STR_PAD_LEFT);

        echo "{$duration} {$client}";
        echo "\n";
    }
    echo "\n";
}

echo ">>> REMAINING";
echo "\n";
foreach ($commitments as $client => $seconds) {
    $remaining = $seconds - ($sums[$client] ?? 0);
    $hours = floor(abs($remaining) / 3600);
    $minutes = floor((abs($remaining) / 60) % 60);
    $duration = ($remaining < 0 ? '-' : '') . $hours . ":" . str_pad((string)$minutes, 2, "0", STR_PAD_LEFT);

    echo "{$duration} {$client}";
    echo "\n";
}
echo "\n";

$secondsAvailable = $sprintStart->add(new DateInterval('P7D'))->getTimestamp() - time();
$daysAvailable = ceil($secondsAvailable / 86400);
$secondsNeeded = $commitments['TOTAL'] - $sums['TOTAL'];
$secondsPerDay = $daysAvailable ? $secondsNeeded / $daysAvailable : 0;
$hours = floor($secondsPerDay / 3600);
$minutes = floor(round($secondsPerDay / 60) % 60);
$duration = $hours . ":" . str_pad((string)$minutes, 2, "0", STR_PAD_LEFT);

echo ">>> CAPACITY";
echo "\n";
echo $daysAvailable . ' days';
echo "\n";
echo ($duration) . ' per day';
echo "\n";

fclose($file);
