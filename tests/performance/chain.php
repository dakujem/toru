<?php

declare(strict_types=1);

use Dakujem\Toru\Itera;

require_once __DIR__ . '/../../vendor/autoload.php';

(function () {
    $fmtt = fn($time) => number_format($time * 1_000) . 'ms (' . number_format($time, 2) . 's)';
    $fmtmb = fn($memo) => ($memo / 1_024 / 1_024) . ' MB';

    $totalNumbers = 10_000_000;

    $setA = [];
    for ($i = 0; $i < $totalNumbers; $i++) {
        $setA[] = rand();
    }
    $setB = [];
    for ($i = 0; $i < $totalNumbers; $i++) {
        $setB[] = rand();
    }
    $setC = [];
    for ($i = 0; $i < $totalNumbers; $i++) {
        $setC[] = rand();
    }

    (function () use ($fmtt, $fmtmb, $setA, $setB, $setC) {
        $startMemo = memory_get_usage();
        $startTime = microtime(true);

        // Merge using array_merge here allocates 500 MB of memory ...
        $set = array_merge($setA, $setB, $setC);

        $mergeTime = microtime(true);
        $mergeMemo = memory_get_usage();

        // ... but iterates twice as fast.
        foreach ($set as $num) {
            //
            $num;
        }

        $iterationTime = microtime(true);
        $iterationMemo = memory_get_usage();

        var_dump([
            'gen_time' => $fmtt($mergeTime - $startTime),
            'gen_memo' => $fmtmb($mergeMemo - $startMemo),
            'iter_time' => $fmtt($iterationTime - $mergeTime),
            'iter_memo' => $fmtmb($iterationMemo - $mergeMemo),
        ]);
    })();

    (function () use ($fmtt, $fmtmb, $setA, $setB, $setC) {
        $startMemo = memory_get_usage();
        $startTime = microtime(true);

        // Chaining allocates virtually no memory ...
        $set = Itera::chain($setA, $setB, $setC);

        $mergeTime = microtime(true);
        $mergeMemo = memory_get_usage();

        // ... but imposes extra overhead and iterates ~50% slower.
        foreach ($set as $num) {
            //
            $num;
        }

        $iterationTime = microtime(true);
        $iterationMemo = memory_get_usage();

        var_dump([
            'gen_time' => $fmtt($mergeTime - $startTime),
            'gen_memo' => $fmtmb($mergeMemo - $startMemo),
            'iter_time' => $fmtt($iterationTime - $mergeTime),
            'iter_memo' => $fmtmb($iterationMemo - $mergeMemo),
        ]);
    })();

})();

