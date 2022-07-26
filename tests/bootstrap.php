<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/ryunosuke/phpunit-extension/inc/bootstrap.php';

\ryunosuke\PHPUnit\Actual::generateStub(__DIR__ . '/../src/HtMarkdown', __DIR__ . '/.stub');
