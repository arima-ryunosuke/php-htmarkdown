<?php

require_once __DIR__ . '/../vendor/autoload.php';

\ryunosuke\PHPUnit\Actual::generateStub(__DIR__ . '/../src/HtMarkdown', __DIR__ . '/.stub');
\ryunosuke\PHPUnit\Exporter\Exporter::insteadOf();

/**
 * @template T
 * @param T $value
 * @return T
 */
function that($value)
{
    return new \ryunosuke\PHPUnit\Actual($value);
}
