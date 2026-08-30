<?php
function mockJudge($code, $problem) {
    if (empty(trim($code))) {
        return [
            'verdict' => 'CE',
            'runtime' => 0,
            'language' => 'cpp',
            'failedTestCase' => 0
        ];
    }

    $roll = mt_rand(1, 100);
    $runtime = mt_rand(20, 320);

    if ($roll <= 45) {
        return [
            'verdict' => 'AC',
            'runtime' => $runtime,
            'language' => 'cpp',
            'failedTestCase' => null
        ];
    } elseif ($roll <= 75) {
        return [
            'verdict' => 'WA',
            'runtime' => $runtime,
            'language' => 'cpp',
            'failedTestCase' => mt_rand(2, 15)
        ];
    } elseif ($roll <= 90) {
        return [
            'verdict' => 'TLE',
            'runtime' => 2000,
            'language' => 'cpp',
            'failedTestCase' => mt_rand(10, 25)
        ];
    } else {
        return [
            'verdict' => 'RE',
            'runtime' => $runtime,
            'language' => 'cpp',
            'failedTestCase' => mt_rand(1, 5)
        ];
    }
}
?>