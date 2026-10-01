<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/autoload.php';
require_once __DIR__ . '/ArabicEvalTest.php';

use EidCloud\ArabicEvalBench\Tests\ArabicEvalTest;

$tester = new ArabicEvalTest();
$passed = $tester->runAll();

exit($passed ? 0 : 1);
