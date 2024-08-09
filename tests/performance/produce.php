<?php

declare(strict_types=1);

use Dakujem\Toru\Itera;

require_once __DIR__ . '/../../vendor/autoload.php';

(function () {
    $fmtt = fn($time) => number_format($time * 1_000) . 'ms (' . number_format($time, 2) . 's)';
    $fmtmb = fn($memo) => ($memo / 1_024 / 1_024) . ' MB';

    $totalNumbers = 10_000_000;

    (function () use ($fmtt, $fmtmb, $totalNumbers) {
        $startMemo = memory_get_usage();
        $startTime = microtime(true);

        // allocates 250 MB of data
        $output = [];
        for ($i = 0; $i < $totalNumbers; $i++) {
            $output[] = rand();
        }

        $procTime = microtime(true);
        $procMemo = memory_get_usage();

        // but runs fast (as expected)
        foreach ($output as $x) {
            //
            $x;
        }

        $iterationTime = microtime(true);
        $iterationMemo = memory_get_usage();

        var_dump([
            'gen_time' => $fmtt($procTime - $startTime),
            'gen_memo' => $fmtmb($procMemo - $startMemo),
            'iter_time' => $fmtt($iterationTime - $procTime),
            'iter_memo' => $fmtmb($iterationMemo - $procMemo),
        ]);
    })();

    (function () use ($fmtt, $fmtmb, $totalNumbers) {
        $startMemo = memory_get_usage();
        $startTime = microtime(true);

        // No memory usage (almost zero)!
        $output = Itera::limit(Itera::produce(fn() => rand()), $totalNumbers);

        $procTime = microtime(true);
        $procMemo = memory_get_usage();

        // But iterates slowly - almost 4 times slower.
        // But remember - here we use little integers, if the array contained bigger data, the performance difference would be smaller.
        foreach ($output as $x) {
            //
            $x;
        }

        $iterationTime = microtime(true);
        $iterationMemo = memory_get_usage();

        var_dump([
            'gen_time' => $fmtt($procTime - $startTime),
            'gen_memo' => $fmtmb($procMemo - $startMemo),
            'iter_time' => $fmtt($iterationTime - $procTime),
            'iter_memo' => $fmtmb($iterationMemo - $procMemo),
        ]);
    })();

    $totalStrings = 50_000;
    $strLen = 1_000;
    $generateRandomString = function (int $length): string {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max = strlen($characters) - 1;
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $max)];
        }
        return $randomString;
    };
    $pregenerated = [];
    for ($i = 0; $i < 10_000; $i++) {
        $pregenerated[] = $generateRandomString($strLen);
    }


    (function () use ($fmtt, $fmtmb, $totalStrings, $strLen, $generateRandomString, $pregenerated) {
        $startMemo = memory_get_usage();
        $startTime = microtime(true);

        $output = [];
        for ($i = 0; $i < $totalStrings; $i++) {
            $output[] = $generateRandomString($strLen);
//            $output[] = $pregenerated[random_int(0, count($pregenerated) - 1)];
        }

        $procTime = microtime(true);
        $procMemo = memory_get_usage();

        foreach ($output as $x) {
            //
            $x;
        }

        $iterationTime = microtime(true);
        $iterationMemo = memory_get_usage();

        var_dump([
            'gen_time' => $fmtt($procTime - $startTime),
            'gen_memo' => $fmtmb($procMemo - $startMemo),
            'iter_time' => $fmtt($iterationTime - $procTime),
            'iter_memo' => $fmtmb($iterationMemo - $procMemo),
        ]);
    })();

    (function () use ($fmtt, $fmtmb, $totalStrings, $strLen, $generateRandomString, $pregenerated) {
        $startMemo = memory_get_usage();
        $startTime = microtime(true);

        $output = Itera::limit(Itera::produce(
            fn() => $generateRandomString($strLen)
//            fn() => $pregenerated[random_int(0, count($pregenerated) - 1)]
        ), $totalStrings);

        $procTime = microtime(true);
        $procMemo = memory_get_usage();

        // In this comparison the generation itself takes 20 seconds,
        // so the real difference between using the iterators or the arrays is negligible.
        // The memory usage is not.
        foreach ($output as $x) {
            //
            $x;
        }

        $iterationTime = microtime(true);
        $iterationMemo = memory_get_usage();

        var_dump([
            'gen_time' => $fmtt($procTime - $startTime),
            'gen_memo' => $fmtmb($procMemo - $startMemo),
            'iter_time' => $fmtt($iterationTime - $procTime),
            'iter_memo' => $fmtmb($iterationMemo - $procMemo),
        ]);
    })();
})();



