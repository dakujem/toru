<?php

declare(strict_types=1);

use Dakujem\Toru\Itera;

require_once __DIR__ . '/../../vendor/autoload.php';

(function () {
    $fmtt = fn($time) => number_format($time * 1_000) . 'ms (' . number_format($time, 2) . 's)';
    $fmtmb = fn($memo) => ($memo / 1_024 / 1_024) . ' MB';

    $filter = fn($i) => 0 == $i % 2; // even numbers only
    $mapper = fn($i, $k) => "{$k}:{$i}";

    $totalNumbers = 10_000_000;

    $input = [];
    for ($i = 0; $i < $totalNumbers; $i++) {
        $input[] = rand();
    }

    (function () use ($fmtt, $fmtmb, $filter, $mapper, $input) {
        $startMemo = memory_get_usage();
        $startTime = microtime(true);

        $filtered = array_filter($input, $filter);
        $output = array_map($mapper, $filtered, array_keys($filtered));

        $procTime = microtime(true);
        $procMemo = memory_get_usage();

        foreach ($output as $x) {
            //
            $x;
        }

        $iterationTime = microtime(true);
        $iterationMemo = memory_get_usage();

        var_dump([
            'proc_time' => $fmtt($procTime - $startTime),
            'proc_memo' => $fmtmb($procMemo - $startMemo),
            'iter_time' => $fmtt($iterationTime - $procTime),
            'iter_memo' => $fmtmb($iterationMemo - $procMemo),
        ]);
    })();

    (function () use ($fmtt, $fmtmb, $filter, $mapper, $input) {
        $startMemo = memory_get_usage();
        $startTime = microtime(true);

        $output = _dash($input)->filter($filter)->map($mapper)->toArray(); // note the conversion to array at this point

        $procTime = microtime(true);
        $procMemo = memory_get_usage();

        foreach ($output as $x) {
            //
            $x;
        }

        $iterationTime = microtime(true);
        $iterationMemo = memory_get_usage();

        var_dump([
            'proc_time' => $fmtt($procTime - $startTime),
            'proc_memo' => $fmtmb($procMemo - $startMemo),
            'iter_time' => $fmtt($iterationTime - $procTime),
            'iter_memo' => $fmtmb($iterationMemo - $procMemo),
        ]);
    })();

    (function () use ($fmtt, $fmtmb, $filter, $mapper, $input) {
        $startMemo = memory_get_usage();
        $startTime = microtime(true);

        $output = _dash($input)->filter($filter)->map($mapper); // note the omission of ->toArray() here

        $procTime = microtime(true);
        $procMemo = memory_get_usage();

        foreach ($output as $x) {
            //
            $x;
        }

        $iterationTime = microtime(true);
        $iterationMemo = memory_get_usage();

        var_dump([
            'proc_time' => $fmtt($procTime - $startTime),
            'proc_memo' => $fmtmb($procMemo - $startMemo),
            'iter_time' => $fmtt($iterationTime - $procTime),
            'iter_memo' => $fmtmb($iterationMemo - $procMemo),
        ]);
    })();

})();

